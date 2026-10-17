<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderReminderTest extends TestCase
{
    use RefreshDatabase;

    private const MAILHIVE = 'api.mailhive.africa/v1/send/emails';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailhive.key' => 'mhs_test_dummy',
            'services.paystack.secret_key' => 'sk_test_dummy',
            'store.support_email' => 'hello@novastaq.com',
        ]);
        $this->app->forgetInstance(PaymentGateway::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'owner'])->save();

        return $admin;
    }

    public function test_artisan_command_sends_recovery_reminder_for_orders_older_than_2_hours(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'msg_reminder', 'status' => 'queued'], 202),
        ]);

        $cat = Category::create(['name' => 'Tech', 'slug' => 'tech', 'type' => 'physical']);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'Gadget A', 'slug' => 'gadget-a', 'type' => 'physical', 'price' => 50_000, 'stock' => 5]);

        // Order 1: 3 hours old, unpaid -> SHOULD be reminded
        $dueOrder = Order::create([
            'customer_name' => 'Dayo Amusan',
            'email' => 'dayo@example.com',
            'phone' => '08012345678',
            'subtotal' => 50_000,
            'total' => 50_000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
        $dueOrder->created_at = now()->subHours(3);
        $dueOrder->saveQuietly();

        $dueOrder->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'physical',
            'unit_price' => 50_000,
            'quantity' => 1,
            'line_total' => 50_000,
        ]);

        // Order 2: 30 minutes old, unpaid -> SHOULD NOT be reminded yet
        $recentOrder = Order::create([
            'customer_name' => 'Recent Buyer',
            'email' => 'recent@example.com',
            'phone' => '08099998888',
            'subtotal' => 50_000,
            'total' => 50_000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'created_at' => now()->subMinutes(30),
        ]);
        $recentOrder->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'delivery_type' => 'physical',
            'unit_price' => 50_000,
            'quantity' => 1,
            'line_total' => 50_000,
        ]);

        // Order 3: 4 hours old, but ALREADY PAID -> SHOULD NOT be reminded
        $paidOrder = Order::create([
            'customer_name' => 'Paid Buyer',
            'email' => 'paid@example.com',
            'phone' => '08055554444',
            'subtotal' => 50_000,
            'total' => 50_000,
            'payment_status' => 'paid',
            'status' => 'processing',
            'created_at' => now()->subHours(4),
        ]);

        // Run artisan command
        $this->artisan('orders:send-reminders')
            ->expectsOutputToContain('Found 1 unpaid order(s) eligible')
            ->expectsOutputToContain('Sent reminder to dayo@example.com')
            ->assertSuccessful();

        $dueOrder->refresh();
        $this->assertNotNull($dueOrder->reminded_at);
        $this->assertTrue($dueOrder->isReminded());

        $recentOrder->refresh();
        $this->assertNull($recentOrder->reminded_at);

        // Verify Mailhive was called with pay link
        Http::assertSent(function ($request) use ($dueOrder) {
            return $request->url() === 'https://'.self::MAILHIVE
                && $request['to'] === 'dayo@example.com'
                && str_contains($request['html'], route('orders.pay', $dueOrder->number));
        });

        // Running it again does NOT re-send because reminded_at is set
        $this->artisan('orders:send-reminders')
            ->expectsOutputToContain('No unpaid orders due for a reminder')
            ->assertSuccessful();
    }

    public function test_customer_can_use_orders_pay_link_to_resume_checkout(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/resume-123'],
            ]),
        ]);

        $order = Order::create([
            'customer_name' => 'Sola Shittu',
            'email' => 'sola@example.com',
            'phone' => '080',
            'subtotal' => 20_000,
            'total' => 20_000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'payment_provider' => 'paystack',
        ]);

        $response = $this->get(URL::temporarySignedRoute('orders.pay', now()->addDays(7), ['order' => $order->number]));

        $response->assertRedirect('https://checkout.paystack.com/resume-123');
    }

    public function test_admin_can_manually_send_payment_reminder(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'msg_manual', 'status' => 'queued'], 202),
        ]);

        $admin = $this->admin();
        $order = Order::create([
            'customer_name' => 'Femi Bakare',
            'email' => 'femi@example.com',
            'phone' => '080',
            'subtotal' => 15_000,
            'total' => 15_000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.reminder', $order));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $order->refresh();
        $this->assertTrue($order->isReminded());
    }
}
