<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_dashboard_url_redirects_customers_to_account(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_url_redirects_staff_to_admin(): void
    {
        $staff = \App\Models\User::factory()->create();
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
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->get('/register')
            ->assertRedirect('/account');
    }
}
