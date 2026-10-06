<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_page_is_hidden_without_the_right_token(): void
    {
        config(['store.install_token' => '']);
        $this->get('/install?token=anything')->assertNotFound();

        config(['store.install_token' => 'secret-token']);
        $this->get('/install')->assertNotFound();
        $this->get('/install?token=wrong')->assertNotFound();
        $this->get('/install?token=secret-token')->assertOk();
    }

    public function test_install_creates_admin_and_categories_then_locks_itself(): void
    {
        config(['store.install_token' => 'secret-token']);

        $this->post('/install', [
            'token' => 'secret-token', 'name' => 'Owner', 'email' => 'owner@novastaq.com',
            'password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(User::where('email', 'owner@novastaq.com')->value('is_admin'));
        $this->assertGreaterThan(0, Category::count());

        // a second visitor with the token can't take over the store
        $this->get('/install?token=secret-token')->assertNotFound();
        $this->post('/install', ['token' => 'secret-token', 'name' => 'X', 'email' => 'x@x.com', 'password' => 'another-pass1', 'password_confirmation' => 'another-pass1'])->assertNotFound();
        $this->assertFalse(User::where('email', 'x@x.com')->exists());
    }
}
