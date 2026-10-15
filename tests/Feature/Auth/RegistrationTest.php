<?php

namespace Tests\Feature\Auth;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_receive_otp_and_verify_to_register(): void
    {
        Http::fake(['https://api.mailhive.africa/*' => Http::response(['id' => 'em_1'])]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register.verify'));
        $this->assertGuest();

        // Verify screen renders
        $this->get(route('register.verify'))->assertOk()->assertSee('test@example.com');

        // Wrong code fails
        $this->post(route('register.verify.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        // Valid OTP creates account and logs in
        $code = EmailOtp::generate('test@example.com', 'registration');
        $verifyRes = $this->post(route('register.verify.store'), ['code' => $code]);

        $this->assertAuthenticated();
        $verifyRes->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'name' => 'Test User']);
    }

    public function test_resending_registration_otp(): void
    {
        Http::fake(['https://api.mailhive.africa/*' => Http::response(['id' => 'em_1'])]);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->post(route('register.resend'))->assertRedirect()->assertSessionHas('status');
    }

    public function test_dashboard_url_redirects_customers_to_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_url_redirects_staff_to_admin(): void
    {
        $staff = User::factory()->create();
        $staff->forceFill(['role' => 'owner'])->save();

        $this->actingAs($staff)->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_url_redirects_guests_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_visiting_register_redirects_safely(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/register')
            ->assertRedirect('/account');
    }
}
