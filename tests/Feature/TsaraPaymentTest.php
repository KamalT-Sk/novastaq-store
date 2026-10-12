<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TsaraPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const TSARA_PUBLIC = 'pk_test_1234567890';
    private const TSARA_SECRET = 'sk_test_0987654321';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.tsara.public_key' => self::TSARA_PUBLIC,
            'services.tsara.secret_key' => self::TSARA_SECRET,
            'services.tsara.base_url' => 'https://api.tsara.ng/v1',
            'store.delivery_fee' => 0,
        ]);
    }

    private function product(int $stock = 5, int $priceKobo = 50_000_00): Product
    {
        $cat = Category::create(['name' => 'Gadgets', 'slug' => 'gadgets']);

        return Product::create([
            'category_id' => $cat->id,
            'name' => 'Power Bank',
            'slug' => 'power-bank',
            'price' => $priceKobo,
            'stock' => $stock,
        ]);
    }

    public function test_checkout_creates_order_and_redirects_to_tsara(): void
    {
        Http::fake([
            'https://api.tsara.ng/v1/checkout' => Http::response([
                'success' => true,
                'status' => 'success',
                'data' => [
                    'id' => 'NS-1234',
                    'status' => 'pending',
                    'checkout_url' => 'https://checkout.tsara.ng/?trx_id=NS-1234',
                ],
            ]),
        ]);

        $item = $this->product(stock: 10, priceKobo: 50_000_00); // ₦50,000

        $this->post(route('cart.add', $item), ['quantity' => 2]); // total = ₦100,000 (10,000,000 kobo)

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'John Tsara',
            'email' => 'john@example.com',
            'phone' => '08012345678',
            'address' => '10 Marina Street',
            'city' => 'Lagos Island',
            'state' => 'Lagos',
            'payment_method' => 'tsara',
        ]);

        $response->assertRedirect('https://checkout.tsara.ng/?trx_id=NS-1234');

        $order = Order::firstOrFail();
        $this->assertSame('tsara', $order->payment_provider);
        $this->assertSame(10_000_000, $order->total);

        Http::assertSent(function ($req) use ($order) {
            return $req->url() === 'https://api.tsara.ng/v1/checkout'
                && $req['public_key'] === self::TSARA_PUBLIC
                && $req['trx_id'] === $order->number
                && $req['amount'] === 100_000 // converted from 10,000,000 kobo to 100,000 Naira
                && $req['currency'] === 'NGN';
        });
    }

    public function test_service_order_redirects_to_tsara(): void
    {
        Http::fake([
            'https://api.tsara.ng/v1/checkout' => Http::response([
                'success' => true,
                'data' => [
                    'checkout_url' => 'https://checkout.tsara.ng/?trx_id=SRV-123',
                ],
            ]),
        ]);

        $cat = Category::create(['name' => 'Services', 'slug' => 'services']);
        $service = Product::create([
            'category_id' => $cat->id,
            'name' => 'X Premium',
            'slug' => 'x-premium',
            'price' => 15_000_00,
            'stock' => null,
            'type' => 'service',
        ]);

        $response = $this->post(route('service.buy', $service), [
            'account_details' => '@novastaqtech',
            'customer_name' => 'Sarah Doe',
            'email' => 'sarah@example.com',
            'phone' => '08099887766',
            'payment_method' => 'tsara',
        ]);

        $response->assertRedirect('https://checkout.tsara.ng/?trx_id=SRV-123');

        $order = Order::firstOrFail();
        $this->assertSame('tsara', $order->payment_provider);
        $this->assertSame(15_000_00, $order->total);
    }

    public function test_tsara_webhook_rejects_bad_signature(): void
    {
        $this->postJson(route('tsara.webhook'), [
            'type' => 'payment.success',
            'data' => ['trx_id' => 'UNKNOWN'],
        ], [
            'X-Tsara-Signature' => 'invalid-hash',
        ])->assertStatus(401);
    }

    public function test_tsara_webhook_marks_order_paid_and_takes_stock_once(): void
    {
        $item = $this->product(stock: 5, priceKobo: 25_000_00);
        $order = Order::create([
            'customer_name' => 'Audu',
            'email' => 'audu@example.com',
            'phone' => '08011223344',
            'address' => 'Test',
            'city' => 'Abuja',
            'state' => 'FCT Abuja',
            'subtotal' => 25_000_00,
            'delivery_fee' => 0,
            'total' => 25_000_00,
            'payment_provider' => 'tsara',
        ]);
        $order->items()->create([
            'product_id' => $item->id,
            'name' => $item->name,
            'unit_price' => $item->price,
            'quantity' => 1,
            'line_total' => $item->price,
        ]);
        $order->update(['payment_reference' => $order->number]);

        Http::fake([
            'https://api.tsara.ng/v1/checkout/status*' => Http::response([
                'success' => true,
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'trx_id' => $order->number,
                    'fiat_amount' => 25000,
                ],
            ]),
        ]);

        $payload = json_encode([
            'type' => 'payment.success',
            'data' => [
                'trx_id' => $order->number,
            ],
        ]);
        $signature = hash_hmac('sha512', $payload, self::TSARA_SECRET);

        $this->call(
            'POST',
            route('tsara.webhook'),
            [],
            [],
            [],
            [
                'HTTP_X_TSARA_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(4, $item->fresh()->stock);

        // Sending again should be idempotent
        $this->call(
            'POST',
            route('tsara.webhook'),
            [],
            [],
            [],
            [
                'HTTP_X_TSARA_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $this->assertSame(4, $item->fresh()->stock);
    }

    public function test_tsara_callback_verifies_payment_and_clears_cart(): void
    {
        $item = $this->product(stock: 3, priceKobo: 10_000_00);
        $order = Order::create([
            'customer_name' => 'Ngozi',
            'email' => 'ngozi@example.com',
            'phone' => '08099881122',
            'address' => 'Enugu road',
            'city' => 'Enugu',
            'state' => 'Enugu',
            'subtotal' => 10_000_00,
            'delivery_fee' => 0,
            'total' => 10_000_00,
            'payment_provider' => 'tsara',
        ]);
        $order->items()->create([
            'product_id' => $item->id,
            'name' => $item->name,
            'unit_price' => $item->price,
            'quantity' => 1,
            'line_total' => $item->price,
        ]);
        $order->update(['payment_reference' => $order->number]);

        Http::fake([
            'https://api.tsara.ng/v1/checkout/status*' => Http::response([
                'success' => true,
                'data' => [
                    'status' => 'successful',
                    'trx_id' => $order->number,
                    'amount' => 10000,
                ],
            ]),
        ]);

        $this->get(route('checkout.callback', [
            'provider' => 'tsara',
            'trx_id' => $order->number,
        ]))->assertOk()->assertSee('Payment received');

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(2, $item->fresh()->stock);
    }

    public function test_tsara_underpayment_rejected(): void
    {
        $item = $this->product(stock: 3, priceKobo: 20_000_00);
        $order = Order::create([
            'customer_name' => 'Emeka',
            'email' => 'emeka@example.com',
            'phone' => '08012345678',
            'address' => 'Owerri',
            'city' => 'Owerri',
            'state' => 'Imo',
            'subtotal' => 20_000_00,
            'delivery_fee' => 0,
            'total' => 20_000_00,
            'payment_provider' => 'tsara',
        ]);
        $order->update(['payment_reference' => $order->number]);

        Http::fake([
            'https://api.tsara.ng/v1/checkout/status*' => Http::response([
                'success' => true,
                'data' => [
                    'status' => 'success',
                    'trx_id' => $order->number,
                    'fiat_amount' => 10000, // half the amount
                ],
            ]),
        ]);

        $payload = json_encode(['type' => 'payment.success', 'data' => ['trx_id' => $order->number]]);
        $signature = hash_hmac('sha512', $payload, self::TSARA_SECRET);

        $this->call(
            'POST',
            route('tsara.webhook'),
            [],
            [],
            [],
            [
                'HTTP_X_TSARA_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $this->assertFalse($order->fresh()->isPaid());
        $this->assertSame(3, $item->fresh()->stock);
    }
}
