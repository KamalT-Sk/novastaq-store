<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EmailOtp;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailTest extends TestCase
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

    private function emails(): Collection
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn (HttpRequest $r) => str_contains($r->url(), 'mailhive'));
    }

    private function paidPhoneOrder(): Order
    {
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $phone = Product::create(['category_id' => $cat->id, 'name' => 'Galaxy A56', 'slug' => 'galaxy-a56', 'price' => 2_000_000, 'stock' => 5]);
        $order = Order::create(['customer_name' => 'Ada Okafor', 'email' => 'ada@example.com', 'phone' => '08012345678', 'address' => '1 Test St', 'city' => 'Ikeja', 'state' => 'Lagos',
            'subtotal' => 2_000_000, 'delivery_fee' => 200_000, 'total' => 2_200_000, 'payment_reference' => 'NV-MAIL']);
        $order->items()->create(['product_id' => $phone->id, 'name' => 'Galaxy A56', 'unit_price' => 2_000_000, 'quantity' => 1, 'line_total' => 2_000_000]);

        return $order;
    }

    public function test_payment_emails_owner_and_customer_once(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'em_1', 'status' => 'queued', 'suppressed' => [], 'test' => true]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 2_200_000]]),
        ]);
        $order = $this->paidPhoneOrder();

        $this->withSession(['order_access' => [$order->id => true]])->get(route('checkout.callback', ['reference' => 'NV-MAIL']))->assertOk();
        $this->withSession(['order_access' => [$order->id => true]])->get(route('checkout.callback', ['reference' => 'NV-MAIL']))->assertOk(); // second confirmation path

        $sent = $this->emails()->values();
        $this->assertCount(3, $sent);
        $this->assertSame('hello@novastaq.com', $sent[0]['to']);
        $this->assertSame('novastaqtechnologies@gmail.com', $sent[1]['to']);
        $this->assertStringContainsString($order->number, $sent[0]['subject']);
        $this->assertStringContainsString($order->number, $sent[1]['subject']);
        $this->assertSame('ada@example.com', $sent[0]['reply_to']); // reply goes to the customer
        $this->assertSame('ada@example.com', $sent[2]['to']);
        $this->assertStringContainsString('₦22,000', $sent[2]['html']);
        $this->assertStringContainsString('Bearer mhs_test_dummy', $sent[0]->header('Authorization')[0]);
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_mailhive_outage_never_blocks_a_payment(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['error' => 'down'], 500),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 2_200_000]]),
        ]);
        $order = $this->paidPhoneOrder();

        $this->withSession(['order_access' => [$order->id => true]])->get(route('checkout.callback', ['reference' => 'NV-MAIL']))->assertOk()->assertSee('Thank you');
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_status_changes_email_the_customer(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'owner'])->save();

        $order = $this->paidPhoneOrder();
        $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'status' => 'processing']);
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'shipped']);
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'shipped']); // no change, no email

        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $x = Product::create(['category_id' => $cat->id, 'name' => 'X Premium (1 month)', 'slug' => 'x', 'type' => 'service', 'price' => 300_000]);
        $plan = Order::create(['customer_name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '0801', 'subtotal' => 300_000, 'total' => 300_000,
            'payment_reference' => 'NV-PLAN', 'payment_status' => 'paid', 'paid_at' => now(), 'status' => 'processing']);
        $plan->items()->create(['product_id' => $x->id, 'name' => $x->name, 'account_details' => '@adaokafor', 'unit_price' => 300_000, 'quantity' => 1, 'line_total' => 300_000]);
        $this->actingAs($admin)->patch(route('admin.orders.update', $plan), ['status' => 'completed']);

        $subjects = $this->emails()->map(fn ($r) => $r['subject'])->values()->all();
        $this->assertSame(["Your order {$order->number} is on its way", 'Your X Premium (1 month) is active'], $subjects);
        $this->assertStringContainsString('@adaokafor', $this->emails()->last()['html']);
    }

    public function test_admin_manually_marks_order_paid_sends_emails_and_deducts_stock(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'owner'])->save();

        $order = $this->paidPhoneOrder();
        $this->assertFalse($order->isPaid());
        $phone = $order->items->first()->product;
        $this->assertSame(5, $phone->stock);

        // Admin marks order paid manually
        $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order))->assertRedirect();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame(4, $phone->fresh()->stock);

        $sent = $this->emails()->values();
        $this->assertCount(3, $sent);
        $this->assertSame('hello@novastaq.com', $sent[0]['to']);
        $this->assertSame('novastaqtechnologies@gmail.com', $sent[1]['to']);
        $this->assertStringContainsString($order->number, $sent[0]['subject']);
        $this->assertSame('ada@example.com', $sent[2]['to']);
        $this->assertSame("Order confirmed: {$order->number}", $sent[2]['subject']);

        // Marking again is idempotent
        $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order))->assertRedirect();
        $this->assertSame(4, $phone->fresh()->stock);
        $this->assertCount(3, $this->emails());
    }

    public function test_admin_delivers_and_cancels_order_sends_emails(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'owner'])->save();

        $order = $this->paidPhoneOrder();
        $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'status' => 'shipped']);

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'delivered']);
        $this->assertSame(["Your order {$order->number} has been delivered"], $this->emails()->map(fn ($r) => $r['subject'])->values()->all());

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'cancelled']);
        $subjects = $this->emails()->map(fn ($r) => $r['subject'])->values()->all();
        $this->assertSame([
            "Your order {$order->number} has been delivered",
            "Your order {$order->number} has been cancelled",
        ], $subjects);
    }

    public function test_website_form_relay_emails_the_inbox_for_novastaq_only(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);
        $payload = ['subject' => 'Software: enquiry from Ada', 'reply_to' => 'ada@example.com', 'fields' => ['Name' => 'Ada', 'Message' => 'We need an app <b>now</b>']];

        $this->withHeader('Origin', 'https://novastaq.com')->postJson('/api/contact', $payload)
            ->assertOk()->assertHeader('Access-Control-Allow-Origin', 'https://novastaq.com');

        $mail = $this->emails()->first();
        $this->assertSame('hello@novastaq.com', $mail['to']);
        $this->assertSame('ada@example.com', $mail['reply_to']);
        $this->assertStringContainsString('&lt;b&gt;now&lt;/b&gt;', $mail['html']); // user input is escaped

        $this->withHeader('Origin', 'https://evil.example')->postJson('/api/contact', $payload)->assertHeaderMissing('Access-Control-Allow-Origin');
        // browser preflight ("may I POST?"): call() takes headers as server variables
        $this->call('OPTIONS', '/api/contact', [], [], [], ['HTTP_ORIGIN' => 'https://novastaq.com', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST'])
            ->assertHeader('Access-Control-Allow-Origin', 'https://novastaq.com');
        $this->call('OPTIONS', '/api/contact', [], [], [], ['HTTP_ORIGIN' => 'https://evil.example', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_password_reset_email_goes_through_mailhive(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);
        config(['mail.default' => 'mailhive', 'mail.from.address' => 'hello@novastaq.com', 'mail.from.name' => 'Novastaq Store']);
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post(route('password.email'), ['email' => 'ada@example.com'])->assertSessionHasNoErrors();

        $mail = $this->emails()->first();
        $this->assertNotNull($mail, 'reset email was not sent');
        $this->assertSame('ada@example.com', $mail['to']);
        $this->assertStringContainsString('password reset code', $mail['subject']);
        $this->assertStringContainsString('Reset your password', $mail['html']);
    }

    public function test_signing_up_sends_a_welcome_email_and_login_links_to_signup(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);

        $this->get(route('login'))->assertOk()->assertSee('Create an account');
        $this->post(route('register'), ['name' => 'Ada Okafor', 'email' => 'ada@example.com', 'password' => 'a-strong-pass-1', 'password_confirmation' => 'a-strong-pass-1'])
            ->assertRedirect(route('register.verify'));

        // First email is the 6-digit verification code
        $otpMail = $this->emails()->first();
        $this->assertSame('ada@example.com', $otpMail['to']);
        $this->assertStringContainsString('verification code', $otpMail['subject']);

        // Completing OTP verification triggers welcome email
        $code = EmailOtp::generate('ada@example.com', 'registration');
        $this->post(route('register.verify.store'), ['code' => $code])
            ->assertRedirect(route('dashboard', absolute: false));

        $welcomeMail = $this->emails()->last();
        $this->assertSame('ada@example.com', $welcomeMail['to']);
        $this->assertSame('Welcome to Novastaq Store', $welcomeMail['subject']);
        $this->assertStringContainsString('Hi Ada', $welcomeMail['html']);
    }

    public function test_website_form_relay_ignores_bots_and_bad_input(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);

        $this->postJson('/api/contact', ['subject' => 'x', 'reply_to' => 'a@b.com', 'fields' => ['m' => 'spam'], 'website' => 'http://spam'])->assertOk();
        $this->postJson('/api/contact', ['subject' => 'x', 'reply_to' => 'not-an-email', 'fields' => ['m' => 'hi']])->assertStatus(422);

        $this->assertCount(0, $this->emails());
    }
}
