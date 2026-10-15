<?php

namespace Tests\Feature\Auth;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Http::fake(['https://api.mailhive.africa/*' => Http::response(['id' => 'em_1'])]);

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('password.reset'));
        $this->assertDatabaseHas('email_otps', [
            'email' => $user->email,
            'purpose' => 'password_reset',
        ]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/reset-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        Http::fake(['https://api.mailhive.africa/*' => Http::response(['id' => 'em_1'])]);

        $user = User::factory()->create();

        $code = EmailOtp::generate($user->email, 'password_reset');

        $response = $this->post('/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'new-secure-password-123',
            'password_confirmation' => 'new-secure-password-123',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secure-password-123', $user->fresh()->password));
    }

    public function test_password_reset_fails_with_invalid_otp(): void
    {
        Http::fake(['https://api.mailhive.africa/*' => Http::response(['id' => 'em_1'])]);

        $user = User::factory()->create();

        EmailOtp::generate($user->email, 'password_reset');

        $response = $this->post('/reset-password', [
            'email' => $user->email,
            'code' => '999999',
            'password' => 'new-secure-password-123',
            'password_confirmation' => 'new-secure-password-123',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_password_can_also_be_reset_with_token_for_staff_invites(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->get('/reset-password/'.$token.'?email='.$user->email);
        $response->assertStatus(200);

        $postRes = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-token-password-123',
            'password_confirmation' => 'new-token-password-123',
        ]);

        $postRes->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-token-password-123', $user->fresh()->password));
    }
}
