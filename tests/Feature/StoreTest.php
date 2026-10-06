<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The money paths: cart → order → Paystack → paid, and who can reach the admin. */
class StoreTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_dummy';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.secret_key' => self::SECRET, 'store.delivery_fee' => 0]);
        $this->app->forgetInstance(\App\Payments\PaymentGateway::class);
    }

    private function phone(int $stock = 5): Product
    {
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        return Product::create(['category_id' => $cat->id, 'name' => 'Test Phone', 'slug' => 'test-phone', 'price' => 45_000_000, 'stock' => $stock]);
    }

    public function test_money_converts_naira_to_kobo_exactly(): void
    {
        $this->assertSame(45_000_000, Money::toKobo('450,000'));
        $this->assertSame(10, Money::toKobo('0.1'));
        $this->assertSame('₦450,000', Money::naira(45_000_000));
    }

    public function test_checkout_creates_order_and_redirects_to_paystack(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']])]);
        $phone = $this->phone();

        $this->post(route('cart.add', $phone), ['quantity' => 2]);
        $this->post(route('checkout.store'), [
            'customer_name' => 'Ada Okafor', 'email' => 'ada@example.com', 'phone' => '08000000000',
            'address' => '1 Test St', 'city' => 'Ikeja', 'state' => 'Lagos',
        ])->assertRedirect('https://checkout.paystack.com/abc');

        $order = Order::firstOrFail();
        $this->assertSame(90_000_000, $order->total); // price comes from the database, not the browser
        Http::assertSent(fn ($req) => $req['amount'] === 90_000_000 && $req['reference'] === $order->number);
    }

    public function test_webhook_rejects_a_bad_signature(): void
    {
        $this->postJson(route('paystack.webhook'), ['event' => 'charge.success'], ['x-paystack-signature' => 'forged'])->assertStatus(401);
    }

    public function test_paid_webhook_marks_order_paid_and_takes_stock_once(): void
    {
        $phone = $this->phone(stock: 5);
        $order = Order::create(['customer_name' => 'Ada', 'email' => 'a@e.com', 'phone' => '0', 'subtotal' => 90_000_000, 'total' => 90_000_000, 'payment_reference' => 'NV-TEST']);
        $order->items()->create(['product_id' => $phone->id, 'name' => $phone->name, 'unit_price' => 45_000_000, 'quantity' => 2, 'line_total' => 90_000_000]);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 90_000_000]])]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'NV-TEST']]);
        $send = fn () => $this->call('POST', route('paystack.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $payload, self::SECRET),
        ], $payload);

        $send()->assertOk();
        $send()->assertOk(); // Paystack retries: must not take stock twice

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame(3, $phone->fresh()->stock);
    }

    public function test_underpayment_does_not_mark_order_paid(): void
    {
        $order = Order::create(['customer_name' => 'Ada', 'email' => 'a@e.com', 'phone' => '0', 'subtotal' => 90_000_000, 'total' => 90_000_000, 'payment_reference' => 'NV-LOW']);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 100]])]);

        $this->get(route('checkout.callback', ['reference' => 'NV-LOW']))->assertOk();

        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_only_admins_reach_the_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_storefront_pages_render(): void
    {
        $phone = $this->phone();
        $this->get('/')->assertOk()->assertSee('Test Phone');
        $this->get(route('product', $phone))->assertOk()->assertSee('₦450,000');
        $this->get(route('category', $phone->category))->assertOk();
    }

    private function xPremium(): Product
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai-subscriptions', 'type' => 'service']);

        return Product::create(['category_id' => $cat->id, 'name' => 'X Premium (1 month)', 'slug' => 'x-premium', 'type' => 'service',
            'account_label' => 'X username or profile link', 'price' => 1_200_000]);
    }

    public function test_service_page_asks_for_the_account(): void
    {
        $this->get(route('product', $this->xPremium()))->assertOk()
            ->assertSee('X username or profile link')
            ->assertSee('Pay ₦12,000 with Paystack')
            ->assertDontSee('Add to cart');
    }

    public function test_buying_a_service_charges_once_without_delivery_and_keeps_the_cart(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 1_200_000]]),
        ]);
        config(['store.delivery_fee' => 2000]);
        $x = $this->xPremium();
        $phone = $this->phone();
        $this->post(route('cart.add', $phone)); // something already in the cart

        $this->post(route('service.buy', $x), [
            'account_details' => '@adaokafor', 'customer_name' => 'Ada Okafor', 'email' => 'ada@example.com', 'phone' => '08012345678',
        ])->assertRedirect('https://checkout.paystack.com/x');

        $order = Order::with('items')->firstOrFail();
        $this->assertSame(1_200_000, $order->total); // no delivery fee on a service
        $this->assertSame('@adaokafor', $order->items->first()->account_details);
        $this->assertTrue($order->isService());

        $this->get(route('checkout.callback', ['reference' => $order->payment_reference]))->assertOk()->assertSee('@adaokafor');
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(1, app(\App\Support\Cart::class)->count()); // the phone is still in the cart
    }

    public function test_service_needs_an_account_and_cannot_go_in_the_cart(): void
    {
        $x = $this->xPremium();
        $this->post(route('service.buy', $x), ['customer_name' => 'A', 'email' => 'a@e.com', 'phone' => '0'])->assertSessionHasErrors('account_details');
        $this->post(route('cart.add', $x))->assertNotFound();
    }

    public function test_admin_sees_paid_plan_in_queue_and_marks_it_done(): void
    {
        $x = $this->xPremium();
        $order = Order::create(['customer_name' => 'Ada', 'email' => 'a@e.com', 'phone' => '0801', 'subtotal' => $x->price, 'total' => $x->price,
            'payment_reference' => 'NV-SVC', 'payment_status' => 'paid', 'paid_at' => now(), 'status' => 'processing']);
        $order->items()->create(['product_id' => $x->id, 'name' => $x->name, 'account_details' => '@adaokafor', 'unit_price' => $x->price, 'quantity' => 1, 'line_total' => $x->price]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Plans to activate')->assertSee('@adaokafor')->assertSee('Mark as done');
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'completed'])->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->actingAs($admin)->get('/admin')->assertSee('Nothing waiting');
    }

    public function test_creating_a_product_returns_a_blank_form_for_the_next_one(): void
    {
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $res = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'iPhone 16', 'category_id' => $cat->id, 'type' => 'physical', 'price' => '1,250,000', 'stock' => 3, 'is_active' => 1,
        ]);

        $p = Product::where("name", "iPhone 16")->firstOrFail();
        $this->assertSame(125_000_000, $p->price);
        $res->assertRedirect(route('admin.products.create', ['category' => $cat->id, 'type' => 'physical', 'added' => $p->id]));
        $this->actingAs($admin)->get($res->headers->get('Location'))->assertOk()
            ->assertSee('iPhone 16</strong> added', false)
            ->assertSee('iPhone 16 Pro Max'); // model suggestions for Phones
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_starter_catalogue_adds_hidden_unpriced_products_once_and_keeps_existing(): void
    {
        $this->phone(); // "Test Phone" already exists
        $existing = Product::create(['category_id' => Category::first()->id, 'name' => 'iPhone 16', 'slug' => 'iphone-16', 'price' => 99_00, 'is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.system.catalogue'))->assertRedirect();
        $added = Product::where('name', '!=', 'Test Phone')->where('name', '!=', 'iPhone 16')->get();

        $this->assertGreaterThan(40, $added->count());
        $this->assertTrue($added->every(fn ($p) => ! $p->is_active && $p->price === 0));
        $this->assertSame('X username or profile link', Product::where('name', 'X Premium (1 month)')->value('account_label'));
        $this->assertSame('service', Product::where('name', 'ChatGPT Plus (1 month)')->value('type'));
        $this->assertTrue($existing->fresh()->is_active); // untouched

        $count = Product::count();
        $this->actingAs($admin)->post(route('admin.system.catalogue'));
        $this->assertSame($count, Product::count()); // running again adds nothing

        $this->get('/')->assertDontSee('iPhone 17 Pro Max'); // hidden products stay off the storefront
        $this->get(route('product', Product::where('name', 'iPhone 17 Pro Max')->first()))->assertNotFound();
    }

    public function test_live_switch_needs_a_price(): void
    {
        $admin = $this->admin();
        $p = $this->phone();
        $p->update(['price' => 0, 'is_active' => false]);

        $this->actingAs($admin)->patch(route('admin.products.toggle', $p))->assertSessionHas('error');
        $this->assertFalse($p->fresh()->is_active);

        $p->update(['price' => 45_000_000]);
        $this->actingAs($admin)->patch(route('admin.products.toggle', $p));
        $this->assertTrue($p->fresh()->is_active);
        $this->actingAs($admin)->patch(route('admin.products.toggle', $p));
        $this->assertFalse($p->fresh()->is_active);
    }

    public function test_product_form_refuses_live_without_price(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $this->actingAs($admin)->post(route('admin.products.store'), ['name' => 'Free Phone', 'category_id' => $cat->id, 'type' => 'physical', 'price' => '0', 'is_active' => 1])
            ->assertSessionHasErrors('price');
        $this->assertFalse(Product::where('name', 'Free Phone')->exists());
    }

    public function test_apply_updates_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.system.update'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->from('/admin')->post(route('admin.system.update'))->assertRedirect('/admin')->assertSessionHas('status');
    }
}
