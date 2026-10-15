<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDigitalKey;
use App\Models\User;
use App\Support\Mailhive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalProductTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_dummy';
    private const MAILHIVE = 'api.mailhive.africa/v1/send/emails';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailhive.key' => 'mhs_test_dummy',
            'services.paystack.secret_key' => self::SECRET,
            'store.support_email' => 'hello@novastaq.com',
        ]);
        $this->app->forgetInstance(\App\Payments\PaymentGateway::class);
    }

    private function category(): Category
    {
        return Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai-subscriptions', 'type' => 'service']);
    }

    private function admin(string $role = 'owner'): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => $role])->save();

        return $admin;
    }

    public function test_admin_can_create_digital_product_with_link_pool_and_manage_keys(): void
    {
        $admin = $this->admin();
        $cat = $this->category();

        $keys = "https://gemini.google.com/invite/code1\nhttps://gemini.google.com/invite/code2\nhttps://gemini.google.com/invite/code3";

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Google Gemini Advanced',
            'category_id' => $cat->id,
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => '15000',
            'digital_instructions' => 'Click link to accept Gemini workspace invite.',
            'add_digital_keys' => $keys,
            'is_active' => '1',
        ]);

        $response->assertRedirect();

        $product = Product::where('name', 'Google Gemini Advanced')->firstOrFail();
        $this->assertTrue($product->isDigital());
        $this->assertSame(3, $product->availableDigitalKeysCount());
        $this->assertSame(1_500_000, $product->price);

        // Edit page shows keys
        $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('3 links available in pool')
            ->assertSee('code1');

        // Admin can delete an unassigned key
        $keyToDelete = $product->digitalKeys()->first();
        $this->actingAs($admin)->delete(route('admin.products.keys.destroy', [$product, $keyToDelete]))
            ->assertRedirect();

        $this->assertSame(2, $product->fresh()->availableDigitalKeysCount());
    }

    public function test_customer_buys_digital_product_and_receives_automatic_link_from_pool(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 1_500_000]]),
            self::MAILHIVE => Http::response(['id' => 'msg_test', 'status' => 'queued'], 202),
        ]);

        $cat = $this->category();
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini Pro Access',
            'slug' => 'gemini-pro-access',
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => 1_500_000,
            'digital_instructions' => 'Click your link to activate.',
            'is_active' => true,
        ]);

        ProductDigitalKey::create(['product_id' => $product->id, 'key_content' => 'https://gemini.invite/link-alpha']);
        ProductDigitalKey::create(['product_id' => $product->id, 'key_content' => 'https://gemini.invite/link-beta']);

        // Customer buys directly
        $response = $this->post(route('digital.buy', $product), [
            'customer_name' => 'Kola Johnson',
            'email' => 'kola@example.com',
            'phone' => '08012345678',
        ]);

        $response->assertRedirect('https://checkout.paystack.com/abc');

        $order = Order::firstOrFail();
        $this->assertSame(1_500_000, $order->total);
        $this->assertSame(0, $order->delivery_fee);
        $this->assertNull($order->address);
        $this->assertTrue($order->isDigital());

        // Callback marks payment verified
        $this->get(route('checkout.callback', ['reference' => $order->number]))->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('completed', $order->status); // Auto-completed because digital delivery succeeded!

        $item = $order->items->first();
        $this->assertSame('https://gemini.invite/link-alpha', $item->digital_content);
        $this->assertSame('Click your link to activate.', $item->digital_instructions);

        // Pool was decremented
        $this->assertSame(1, $product->fresh()->availableDigitalKeysCount());

        // Customer sees link and copy button on complete screen
        $this->get(route('checkout.callback', ['reference' => $order->number]))
            ->assertSee('Your Digital Delivery')
            ->assertSee('https://gemini.invite/link-alpha')
            ->assertSee('Copy link');
    }

    public function test_customer_buys_when_pool_is_empty_stays_processing_and_admin_delivers_manually(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 2_000_000]]),
            self::MAILHIVE => Http::response(['id' => 'msg_test', 'status' => 'queued'], 202),
        ]);

        $admin = $this->admin();
        $cat = $this->category();
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini AI Custom',
            'slug' => 'gemini-ai-custom',
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => 2_000_000,
            'is_active' => true,
        ]);

        // Pool is completely empty (0 keys)
        $this->assertSame(0, $product->availableDigitalKeysCount());

        $this->post(route('digital.buy', $product), [
            'customer_name' => 'Bisi Bello',
            'email' => 'bisi@example.com',
            'phone' => '08099998888',
        ])->assertRedirect('https://checkout.paystack.com/abc');

        $order = Order::firstOrFail();
        $this->get(route('checkout.callback', ['reference' => $order->number]))->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('processing', $order->status); // Remains processing waiting for admin
        $this->assertNull($order->items->first()->digital_content);

        // Order complete screen informs user link is being prepared
        $this->get(route('checkout.callback', ['reference' => $order->number]))
            ->assertSee('Link being prepared');

        // Admin views the order and sees the manual delivery prompt
        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertSee('Needs Digital Link');

        // Admin delivers the link
        $this->actingAs($admin)->post(route('admin.orders.deliver-digital', [$order, $order->items->first()]), [
            'digital_content' => 'https://gemini.google.com/invite/manual-code-xyz',
        ])->assertRedirect();

        $order->refresh();
        $item = $order->items->first()->fresh();
        $this->assertSame('https://gemini.google.com/invite/manual-code-xyz', $item->digital_content);
        $this->assertSame('completed', $order->status);

        // Mailhive should have sent the digital-delivered email to bisi@example.com
        Http::assertSent(fn ($req) => str_contains($req->url(), 'mailhive') &&
            isset($req['to']) && $req['to'] === 'bisi@example.com' &&
            str_contains($req['subject'], 'Access ready')
        );
    }

    public function test_digital_file_download_endpoint(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('course-guide.pdf', 500);
        $path = $file->store('digital', 'public');

        $cat = $this->category();
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Digital Marketing Guide',
            'slug' => 'digital-marketing-guide',
            'type' => 'digital',
            'digital_type' => 'file',
            'digital_file' => $path,
            'price' => 500_000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_name' => 'Tunde',
            'email' => 'tunde@example.com',
            'phone' => '080',
            'subtotal' => 500_000,
            'delivery_fee' => 0,
            'total' => 500_000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'digital',
            'digital_file' => $path,
            'unit_price' => 500_000,
            'quantity' => 1,
            'line_total' => 500_000,
        ]);

        // Download succeeds for paid order
        $this->get(route('orders.download', ['order' => $order->number, 'item' => $item->id]))
            ->assertOk()
            ->assertHeader('content-disposition');

        // Unpaid order rejects download
        $unpaidOrder = Order::create([
            'customer_name' => 'Unpaid',
            'email' => 'unpaid@example.com',
            'phone' => '080',
            'subtotal' => 500_000,
            'total' => 500_000,
            'payment_status' => 'unpaid',
        ]);
        $unpaidItem = $unpaidOrder->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'digital',
            'digital_file' => $path,
            'unit_price' => 500_000,
            'quantity' => 1,
            'line_total' => 500_000,
        ]);

        $this->get(route('orders.download', ['order' => $unpaidOrder->number, 'item' => $unpaidItem->id]))
            ->assertStatus(403);
    }
}
