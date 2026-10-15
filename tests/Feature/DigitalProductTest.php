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

    public function test_parse_digital_keys_joins_multi_line_wrapped_urls_into_single_links(): void
    {
        $controller = app(\App\Http\Controllers\Admin\ProductController::class);

        // A paste of 2 URLs, where each URL was line-broken into 3 visual lines by copy-pasting
        $paste = "https://serviceactivation.id/subscription/new/gAAAAABx4dpFfX928z6TV\n_8xt5SVk6tPeXRbiWSzJGsem9cFmg3IYeb89mjUixMW-HVqZ-\nMCWbJC5kTEYUwWe8AUApNLA==\n"
               ."https://serviceactivation.id/subscription/new/gAAAAABsecondLink_part1\n_part2-\npart3==";

        $parsed = $controller->parseDigitalKeys($paste);

        $this->assertCount(2, $parsed);
        $this->assertSame(
            'https://serviceactivation.id/subscription/new/gAAAAABx4dpFfX928z6TV_8xt5SVk6tPeXRbiWSzJGsem9cFmg3IYeb89mjUixMW-HVqZ-MCWbJC5kTEYUwWe8AUApNLA==',
            $parsed[0]
        );
        $this->assertSame(
            'https://serviceactivation.id/subscription/new/gAAAAABsecondLink_part1_part2-part3==',
            $parsed[1]
        );
    }

    public function test_parse_digital_keys_splits_glued_urls_without_newlines(): void
    {
        $controller = app(\App\Http\Controllers\Admin\ProductController::class);

        // Exactly like user input: URL 3 ends in == and URL 4 starts immediately with https://
        $paste = "https://gemini-pro-18m.com/subscription/new/key1\n"
               . "https://gemini-pro-18m.com/subscription/new/key2\n"
               . "https://serviceactivation.id/subscription/new/key3_token==https://serviceactivation.id/subscription/new/key4_token==";

        $parsed = $controller->parseDigitalKeys($paste);

        $this->assertCount(4, $parsed);
        $this->assertSame('https://gemini-pro-18m.com/subscription/new/key1', $parsed[0]);
        $this->assertSame('https://gemini-pro-18m.com/subscription/new/key2', $parsed[1]);
        $this->assertSame('https://serviceactivation.id/subscription/new/key3_token==', $parsed[2]);
        $this->assertSame('https://serviceactivation.id/subscription/new/key4_token==', $parsed[3]);
    }

    public function test_multi_quantity_order_dispenses_multiple_keys_and_renders_full_links(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'msg_test', 'status' => 'queued'], 202),
        ]);

        $cat = $this->category();
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini Pro Multi',
            'slug' => 'gemini-pro-multi',
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => 1_500_000,
            'is_active' => true,
        ]);

        ProductDigitalKey::create(['product_id' => $product->id, 'key_content' => 'https://gemini-pro-18m.com/subscription/new/key1_full_url_token_12345']);
        ProductDigitalKey::create(['product_id' => $product->id, 'key_content' => 'https://gemini-pro-18m.com/subscription/new/key2_full_url_token_67890']);

        // Order with quantity = 2
        $order = Order::create([
            'customer_name' => 'Alice Multi',
            'email' => 'alice@example.com',
            'phone' => '08011223344',
            'subtotal' => 3_000_000,
            'total' => 3_000_000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'digital',
            'unit_price' => 1_500_000,
            'quantity' => 2,
            'line_total' => 3_000_000,
        ]);

        // Fulfill payment
        $payment = new \App\Payments\VerifiedPayment(
            paid: true,
            amountKobo: 3_000_000,
            reference: $order->number,
        );

        app(\App\Support\Fulfilment::class)->markOrderPaid($order, $payment, 'paystack');

        $order->refresh();
        $item->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('completed', $order->status);
        $this->assertCount(2, $item->digitalLinks());
        $this->assertStringContainsString('key1_full_url_token_12345', $item->digitalLinks()[0]);
        $this->assertStringContainsString('key2_full_url_token_67890', $item->digitalLinks()[1]);

        // Verify order complete view displays full links and multiple links UI
        $response = $this->get(route('checkout.callback', ['reference' => $order->number]));
        $response->assertOk();
        $response->assertSee('Copy all 2 links');
        $response->assertSee('Personal Link #1');
        $response->assertSee('Personal Link #2');
        $response->assertSee('key1_full_url_token_12345');
        $response->assertSee('key2_full_url_token_67890');
    }

    public function test_admin_can_remove_key_via_ajax_endpoint(): void
    {
        $admin = $this->admin();
        $cat = $this->category();

        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Canva Pro Links',
            'slug' => 'canva-pro-links',
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => 1_000_000,
            'is_active' => true,
        ]);

        $key1 = ProductDigitalKey::create(['product_id' => $product->id, 'key_content' => 'https://canva.com/invite/one']);
        $key2 = ProductDigitalKey::create(['product_id' => $product->id, 'key_content' => 'https://canva.com/invite/two']);

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.keys.destroy', [$product, $key1]));
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'available_count' => 1,
                'total_count' => 1,
            ]);

        $this->assertDatabaseMissing('product_digital_keys', ['id' => $key1->id]);
        $this->assertDatabaseHas('product_digital_keys', ['id' => $key2->id]);
    }

    public function test_numbered_links_parsing_does_not_corrupt_urls(): void
    {
        $admin = $this->admin();
        $cat = $this->category();

        $longLink1 = 'https://gemini.linkonward.com/subscription/new/2A4rl4Tr3xOt5MYhvPReI60E2WrE9OlhVNbgOVDSS1YdV47X62OWcLVs4T7qWbk1IQEuck-u0UgdKs1nncsTWPnpWiHHs2Lolnndcy8p7J58wHg4z7_8CO1lPqgIs-hbjVaQA9tT1-phGdXpzWKjgVz8ucAYwUhReNNQyOcSU_LNTiHichKiZYwAH440vW6FlibuDgmPRt-XdGQO-5sQmCSAkImm4N9c6sozuBdiSbHXfe1Go0SpivkXC-9F_C1h';
        $link2 = 'https://gemini.linkonward.com/subscription/new/rmXyQ-KOej4DLq3VgU_oXYZ';
        $link3 = 'https://gemini.linkonward.com/subscription/new/thirdOption789';

        $pastedInput = "1. {$longLink1}\n2. {$link2}\n3) {$link3}";

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Gemini Numbered Test',
            'category_id' => $cat->id,
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => '10000',
            'add_digital_keys' => $pastedInput,
            'is_active' => '1',
        ]);

        $product = Product::where('name', 'Gemini Numbered Test')->firstOrFail();
        $keys = $product->digitalKeys()->pluck('key_content')->all();

        $this->assertCount(3, $keys);
        // Specifically assert that key 1 does NOT have '2.' appended to it
        $this->assertSame($longLink1, $keys[0]);
        $this->assertStringEndsWith('C1h', $keys[0]);
        $this->assertFalse(str_ends_with($keys[0], '2.'));

        // Specifically assert that key 2 does NOT have '3)' appended to it
        $this->assertSame($link2, $keys[1]);
        $this->assertSame($link3, $keys[2]);
    }

    public function test_order_item_digital_links_sanitizes_trailing_list_marker(): void
    {
        $cat = $this->category();
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'digital',
            'price' => 500_000,
        ]);

        $order = Order::create([
            'customer_name' => 'Test Customer',
            'email' => 'buyer@example.com',
            'phone' => '080',
            'subtotal' => 500_000,
            'total' => 500_000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'digital',
            'digital_content' => 'https://gemini.linkonward.com/sub/testTokenAlpha2.',
            'unit_price' => 500_000,
            'quantity' => 1,
            'line_total' => 500_000,
        ]);

        // When retrieved via digitalLinks(), trailing '2.' must be stripped
        $links = $item->digitalLinks();
        $this->assertCount(1, $links);
        $this->assertSame('https://gemini.linkonward.com/sub/testTokenAlpha', $links[0]);
    }

    public function test_admin_can_update_and_resend_digital_link_and_mark_completed(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'msg_resend_test', 'status' => 'queued'], 202),
        ]);

        $admin = $this->admin();
        $cat = $this->category();
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini Subscription',
            'slug' => 'gemini-subscription',
            'type' => 'digital',
            'price' => 1_000_000,
        ]);

        $order = Order::create([
            'customer_name' => 'Chineze',
            'email' => 'chineze@example.com',
            'phone' => '07031667744',
            'subtotal' => 1_000_000,
            'total' => 1_000_000,
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'digital',
            'digital_content' => 'https://gemini.linkonward.com/sub/oldcorrupted2.',
            'unit_price' => 1_000_000,
            'quantity' => 1,
            'line_total' => 1_000_000,
        ]);

        $newLink = 'https://gemini.linkonward.com/subscription/new/cleanValidToken123';

        // Admin updates the link and marks the order as completed
        $response = $this->actingAs($admin)->post(route('admin.orders.deliver-digital', [$order, $item]), [
            'digital_content' => $newLink,
            'send_email' => '1',
            'mark_completed' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $order->refresh();
        $item->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertSame($newLink, $item->digital_content);
        $this->assertSame([$newLink], $item->digitalLinks());

        // Assert delivery email was sent to customer
        Http::assertSent(fn ($req) => str_contains($req->url(), 'mailhive') &&
            isset($req['to']) && $req['to'] === 'chineze@example.com' &&
            str_contains($req['subject'], 'Access ready')
        );
    }
}
