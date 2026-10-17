<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NombaPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const NOMBA_CLIENT_ID = 'client_test_123';

    private const NOMBA_CLIENT_SECRET = 'secret_test_456';

    private const NOMBA_ACCOUNT_ID = 'acc_test_789';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.nomba.client_id' => self::NOMBA_CLIENT_ID,
            'services.nomba.client_secret' => self::NOMBA_CLIENT_SECRET,
            'services.nomba.account_id' => self::NOMBA_ACCOUNT_ID,
            'services.nomba.base_url' => 'https://api.nomba.com',
            'store.delivery_fee' => 0,
        ]);
        Cache::flush();
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

    public function test_checkout_creates_order_and_redirects_to_nomba(): void
    {
        Http::fake([
            'https://api.nomba.com/v1/auth/token/issue' => Http::response([
                'code' => '00',
                'data' => [
                    'access_token' => 'mock_token_abc',
                    'expires_in' => 1800,
                ],
            ]),
            'https://api.nomba.com/v1/checkout/order' => Http::response([
                'code' => '00',
                'data' => [
                    'orderReference' => 'NOMBA-REF-1',
                    'checkoutLink' => 'https://checkout.nomba.com/pay/NOMBA-REF-1',
                ],
            ]),
        ]);

        $item = $this->product(stock: 10, priceKobo: 50_000_00);

        $this->post(route('cart.add', $item), ['quantity' => 2]);

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Chidi Nomba',
            'email' => 'chidi@example.com',
            'phone' => '08012345678',
            'address' => '15 Victoria Island',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'payment_method' => 'nomba',
        ]);

        $response->assertRedirect('https://checkout.nomba.com/pay/NOMBA-REF-1');

        $order = Order::firstOrFail();
        $this->assertSame('nomba', $order->payment_provider);
        $this->assertSame(10_000_000, $order->total);

        Http::assertSent(function ($req) {
            return $req->url() === 'https://api.nomba.com/v1/checkout/order'
                && $req->hasHeader('Authorization', 'Bearer mock_token_abc')
                && $req->hasHeader('accountId', self::NOMBA_ACCOUNT_ID)
                && $req['order']['amount'] === '100000.00';
        });
    }

    public function test_nomba_webhook_rejects_bad_signature(): void
    {
        $this->postJson(route('nomba.webhook'), [
            'event' => 'payment_success',
            'data' => ['orderReference' => 'UNKNOWN'],
        ], [
            'nomba-signature' => 'invalid-hash',
        ])->assertStatus(401);
    }

    public function test_nomba_webhook_marks_order_paid_and_takes_stock_once(): void
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
            'payment_provider' => 'nomba',
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
            'https://api.nomba.com/v1/auth/token/issue' => Http::response([
                'data' => ['access_token' => 'token_xyz'],
            ]),
            'https://api.nomba.com/v1/transactions/accounts/single*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'SUCCESS',
                    'amount' => 25000.00,
                    'orderReference' => $order->number,
                ],
            ]),
        ]);

        $payload = json_encode([
            'event' => 'payment_success',
            'data' => [
                'orderReference' => $order->number,
            ],
        ]);
        $signature = hash_hmac('sha256', $payload, self::NOMBA_CLIENT_SECRET);

        $this->call(
            'POST',
            route('nomba.webhook'),
            [],
            [],
            [],
            [
                'HTTP_NOMBA_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(4, $item->fresh()->stock);

        // Idempotency: second time changes nothing
        $this->call(
            'POST',
            route('nomba.webhook'),
            [],
            [],
            [],
            [
                'HTTP_NOMBA_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $this->assertSame(4, $item->fresh()->stock);
    }

    public function test_nomba_callback_verifies_payment(): void
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
            'payment_provider' => 'nomba',
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
            'https://api.nomba.com/v1/auth/token/issue' => Http::response([
                'data' => ['access_token' => 'token_xyz'],
            ]),
            'https://api.nomba.com/v1/transactions/accounts/single*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'SUCCESS',
                    'amount' => 10000.00,
                    'orderReference' => $order->number,
                ],
            ]),
        ]);

        $this->withSession(['order_access' => [$order->id => true]])->get(route('checkout.callback', [
            'provider' => 'nomba',
            'orderReference' => $order->number,
        ]))->assertOk()->assertSee('Payment received');

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(2, $item->fresh()->stock);
    }
}
