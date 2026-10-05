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
}
