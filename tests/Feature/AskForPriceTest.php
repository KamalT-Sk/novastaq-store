<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AskForPriceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'owner'])->save();

        return $user;
    }

    public function test_admin_can_create_ask_for_price_product_with_zero_price_and_live(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);

        $res = $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'Factory 12months',
            'category_id' => $cat->id,
            'type' => 'service',
            'account_label' => 'Email Account',
            'ask_for_price' => '1',
            'stock' => '1',
            'is_active' => '1',
            'short_description' => 'Autonomous AI coding agent.',
            'description' => 'Full enterprise droid access.',
        ]);

        $res->assertSessionHasNoErrors();

        $product = Product::where('slug', 'factory-12months')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->isPriceOnDemand());
        $this->assertTrue($product->is_active);
        $this->assertSame(0, $product->price);
        $this->assertSame(1, $product->stock);
        $this->assertSame('Price on request', $product->priceDisplay());
    }

    public function test_admin_can_toggle_live_on_ask_for_price_product(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'name' => 'Gumloop Pro',
            'slug' => 'gumloop-pro',
            'category_id' => $cat->id,
            'type' => 'service',
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => false,
        ]);

        $res = $this->actingAs($this->admin())->patch(route('admin.products.toggle', $product));
        $res->assertSessionHas('status');
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_admin_can_switch_from_ask_for_price_to_fixed_price(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'name' => 'Manus Pro',
            'slug' => 'manus-pro',
            'category_id' => $cat->id,
            'type' => 'service',
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'name' => 'Manus Pro',
            'category_id' => $cat->id,
            'type' => 'service',
            'price' => '35,000',
            'ask_for_price' => '0',
            'is_active' => '1',
        ]);

        $res->assertSessionHasNoErrors();
        $fresh = $product->fresh();
        $this->assertFalse($fresh->isPriceOnDemand());
        $this->assertSame(3_500_000, $fresh->price);
    }

    public function test_storefront_displays_ask_for_price_and_whatsapp_button(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'name' => 'Lovable Pro 12months',
            'slug' => 'lovable-pro-12months',
            'category_id' => $cat->id,
            'type' => 'service',
            'account_label' => 'Email Account',
            'stock' => 2,
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => true,
            'short_description' => 'Full-stack AI app builder.',
            'description' => 'Build apps fast with Lovable Pro.',
        ]);

        $res = $this->get(route('product', $product));
        $res->assertOk();
        $res->assertSee('Ask for price');
        $res->assertSee('Price Available on Request');
        $res->assertSee('Ask for Price on WhatsApp');
        $res->assertSee('2 spots available');
        $res->assertSee(e($product->whatsappInquiryUrl()), false);

        // Also test the catalog page renders card properly
        $catRes = $this->get(route('category', $cat));
        $catRes->assertOk();
        $catRes->assertSee('Ask for price');
        $catRes->assertSee('2 spots left');
    }

    public function test_direct_service_order_checkout_is_blocked_for_ask_for_price(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'name' => 'Resend Pro 12months',
            'slug' => 'resend-pro-12months',
            'category_id' => $cat->id,
            'type' => 'service',
            'account_label' => 'Email Account',
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => true,
        ]);

        $res = $this->post(route('service.buy', $product), [
            'account_details' => 'dev@example.com',
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08012345678',
        ]);

        $res->assertNotFound();
    }

    public function test_direct_cart_addition_is_blocked_for_ask_for_price(): void
    {
        $cat = Category::create(['name' => 'Hardware', 'slug' => 'hardware', 'type' => 'physical']);
        $product = Product::create([
            'name' => 'Custom Server Rack',
            'slug' => 'custom-server-rack',
            'category_id' => $cat->id,
            'type' => 'physical',
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => true,
        ]);

        $res = $this->post(route('cart.add', $product), [
            'quantity' => 1,
        ]);

        $res->assertNotFound();
    }
}
