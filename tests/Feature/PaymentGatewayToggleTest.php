<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaymentGatewayToggleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'owner'): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => $role])->save();

        return $admin;
    }

    private function product(): Product
    {
        $cat = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        return Product::create([
            'category_id' => $cat->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'price' => 100_000_00,
            'stock' => 5,
        ]);
    }

    public function test_non_owner_cannot_access_payment_settings(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('admin.payments.index'))->assertForbidden();

        $manager = $this->admin('manager');
        $this->actingAs($manager)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_owner_can_view_gateways_and_toggle_them(): void
    {
        $owner = $this->admin('owner');

        $this->actingAs($owner)->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Paystack')
            ->assertSee('Tsara')
            ->assertSee('Nomba');

        // Toggle Tsara off
        $this->actingAs($owner)->post(route('admin.payments.toggle', 'tsara'))->assertRedirect();
        $this->assertFalse(Setting::isGatewayEnabled('tsara'));

        // Toggle Tsara back on
        $this->actingAs($owner)->post(route('admin.payments.toggle', 'tsara'))->assertRedirect();
        $this->assertTrue(Setting::isGatewayEnabled('tsara'));
    }

    public function test_disabled_gateway_is_rejected_on_checkout(): void
    {
        Setting::setGatewayEnabled('tsara', false);
        $this->assertFalse(Setting::isGatewayEnabled('tsara'));

        $item = $this->product();
        $this->post(route('cart.add', $item), ['quantity' => 1]);

        // Attempting checkout with disabled 'tsara' gateway should fail validation
        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '08012345678',
            'address' => 'Test Address',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'payment_method' => 'tsara',
        ]);

        $response->assertSessionHasErrors('payment_method');
    }

    public function test_cannot_disable_all_gateways(): void
    {
        $owner = $this->admin('owner');

        // Disable Paystack and Tsara
        Setting::setGatewayEnabled('paystack', false);
        Setting::setGatewayEnabled('tsara', false);

        // Only Nomba is left
        $this->assertSame(['nomba'], Setting::enabledGateways());

        // Attempting to disable Nomba too should fail
        $this->actingAs($owner)->post(route('admin.payments.toggle', 'nomba'))
            ->assertSessionHas('error');

        $this->assertTrue(Setting::isGatewayEnabled('nomba'));
    }
}
