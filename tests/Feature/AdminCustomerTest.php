<?php

namespace Tests\Feature;

use App\Models\EmailBroadcast;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    private const MAILHIVE = 'api.mailhive.africa/v1/send/emails';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailhive.key' => 'mhs_test_dummy',
            'store.support_email' => 'hello@novastaq.com',
        ]);
    }

    private function admin(string $role = 'owner'): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => $role])->save();

        return $admin;
    }

    public function test_admin_can_view_customers_directory_with_registered_and_guest_buyers(): void
    {
        $admin = $this->admin();

        // 1. Registered user
        $registered = User::factory()->create([
            'name' => 'Chioma Adebayo',
            'email' => 'chioma@example.com',
            'phone' => '08011112222',
        ]);

        // Order by registered user
        Order::create([
            'user_id' => $registered->id,
            'customer_name' => 'Chioma Adebayo',
            'email' => 'chioma@example.com',
            'phone' => '08011112222',
            'subtotal' => 500_000,
            'total' => 500_000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        // 2. Guest buyer (no user account)
        Order::create([
            'user_id' => null,
            'customer_name' => 'Emeka Okafor',
            'email' => 'emeka@example.com',
            'phone' => '08033334444',
            'subtotal' => 750_000,
            'total' => 750_000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertSee('Customers & Users');
        $response->assertSee('Chioma Adebayo');
        $response->assertSee('chioma@example.com');
        $response->assertSee('Emeka Okafor');
        $response->assertSee('emeka@example.com');
        $response->assertSee('Guest');
        $response->assertSee('Send Bulk Email');
    }

    public function test_admin_can_search_customers(): void
    {
        $admin = $this->admin();

        User::factory()->create(['name' => 'David Adeleke', 'email' => 'david@example.com']);
        User::factory()->create(['name' => 'Burna Boy', 'email' => 'burna@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.customers.index', ['q' => 'adeleke']));
        $response->assertOk();
        $response->assertSee('David Adeleke');
        $response->assertDontSee('Burna Boy');
    }

    public function test_admin_can_view_broadcast_form_with_audience_counts(): void
    {
        $admin = $this->admin();

        User::factory()->create(['email' => 'user1@example.com']);
        User::factory()->create(['email' => 'user2@example.com']);

        Order::create([
            'customer_name' => 'Guest One',
            'email' => 'guest1@example.com',
            'phone' => '080',
            'subtotal' => 100,
            'total' => 100,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.customers.broadcast'));

        $response->assertOk();
        $response->assertSee('Send Bulk Email');
        $response->assertSee('Target Audience');
        $response->assertSee('Email Subject Line');
    }

    public function test_admin_can_send_test_broadcast_email(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'msg_test', 'status' => 'queued'], 202),
        ]);

        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.customers.broadcast.test'), [
            'subject' => 'Weekend Flash Sale',
            'eyebrow' => 'Novastaq Store Special',
            'headline' => 'Get 20% off all digital licenses',
            'content' => 'Hello team, check out our weekend discounts on ChatGPT & Gemini.',
            'button_label' => 'Shop Flash Sale',
            'button_url' => 'https://store.novastaq.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Http::assertSent(function ($request) use ($admin) {
            return $request->url() === 'https://'.self::MAILHIVE
                && $request['to'] === $admin->email
                && str_contains($request['subject'], '[TEST PREVIEW] Weekend Flash Sale');
        });
    }

    public function test_admin_can_send_bulk_broadcast_to_customers(): void
    {
        Http::fake([
            self::MAILHIVE => Http::response(['id' => 'msg_broadcast', 'status' => 'queued'], 202),
        ]);

        $admin = $this->admin();

        User::factory()->create(['email' => 'customer_a@example.com']);
        User::factory()->create(['email' => 'customer_b@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.customers.broadcast.send'), [
            'audience' => 'all',
            'subject' => 'Big Announcement for Novastaq Users',
            'eyebrow' => 'Official Update',
            'headline' => 'New Digital Licenses Are Here',
            'content' => 'We have just added Google Gemini and ChatGPT instant subscriptions!',
            'button_label' => 'Explore Store',
            'button_url' => 'https://store.novastaq.com',
        ]);

        $response->assertRedirect(route('admin.customers.broadcast'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('email_broadcasts', [
            'subject' => 'Big Announcement for Novastaq Users',
            'audience' => 'all',
            'headline' => 'New Digital Licenses Are Here',
        ]);

        $broadcast = EmailBroadcast::first();
        $this->assertNotNull($broadcast);
        $this->assertGreaterThanOrEqual(2, $broadcast->recipients_count);
    }
}
