<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'owner'])->save();

        return $user;
    }

    public function test_admin_can_create_product_specific_discount_code(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Google Gemini Advanced',
            'slug' => 'google-gemini-advanced',
            'type' => 'service',
            'price' => 1_000_000, // ₦10,000 in kobo
            'is_active' => true,
        ]);

        $res = $this->actingAs($admin)->post(route('admin.discounts.store'), [
            'code' => 'gemini25',
            'percent_off' => 25,
            'product_id' => $product->id,
            'max_uses' => 10,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('discount_codes', [
            'code' => 'GEMINI25',
            'percent_off' => 25,
            'product_id' => $product->id,
            'max_uses' => 10,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_discount_code_directly_from_product_edit_page(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini Pro',
            'slug' => 'gemini-pro',
            'type' => 'service',
            'price' => 1_000_000, // ₦10,000
            'is_active' => true,
        ]);

        $res = $this->actingAs($admin)->post(route('admin.products.discounts.store', $product), [
            'code' => 'SAVE25',
            'percent_off' => 25,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('discount_codes', [
            'code' => 'SAVE25',
            'percent_off' => 25,
            'product_id' => $product->id,
        ]);
    }

    public function test_discount_check_json_endpoint_calculates_savings_correctly(): void
    {
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini 10k',
            'slug' => 'gemini-10k',
            'type' => 'service',
            'price' => 1_000_000, // ₦10,000 in kobo
            'is_active' => true,
        ]);

        DiscountCode::create([
            'code' => 'GEMINI25',
            'percent_off' => 25,
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $res = $this->postJson(route('discount.check'), [
            'code' => 'gemini25',
            'product_id' => $product->id,
            'subtotal' => 1_000_000,
        ]);

        $res->assertOk()
            ->assertJson([
                'valid' => true,
                'code' => 'GEMINI25',
                'percent_off' => 25,
                'discount_amount' => 250_000, // ₦2,500
                'discount_formatted' => '₦2,500',
                'original_price' => 1_000_000,
                'final_price' => 750_000, // ₦7,500
                'final_price_formatted' => '₦7,500',
            ]);
    }

    public function test_discount_check_rejects_wrong_product_or_expired_code(): void
    {
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai', 'type' => 'service']);
        $product1 = Product::create(['category_id' => $cat->id, 'name' => 'Product 1', 'slug' => 'p1', 'type' => 'service', 'price' => 100_00, 'is_active' => true]);
        $product2 = Product::create(['category_id' => $cat->id, 'name' => 'Product 2', 'slug' => 'p2', 'type' => 'service', 'price' => 100_00, 'is_active' => true]);

        DiscountCode::create([
            'code' => 'P1ONLY',
            'percent_off' => 10,
            'product_id' => $product1->id,
            'is_active' => true,
        ]);

        // Wrong product
        $this->postJson(route('discount.check'), [
            'code' => 'P1ONLY',
            'product_id' => $product2->id,
        ])->assertStatus(422)->assertJson(['valid' => false]);

        // Inactive code
        DiscountCode::create([
            'code' => 'INACTIVE',
            'percent_off' => 10,
            'is_active' => false,
        ]);
        $this->postJson(route('discount.check'), ['code' => 'INACTIVE'])->assertStatus(422);

        // Expired code
        DiscountCode::create([
            'code' => 'EXPIRED',
            'percent_off' => 10,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);
        $this->postJson(route('discount.check'), ['code' => 'EXPIRED'])->assertStatus(422);
    }

    public function test_customer_buys_service_for_10k_with_25_percent_code_and_pays_7500(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/discount-test'],
            ]),
        ]);

        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini 1.5 Pro',
            'slug' => 'gemini-15-pro',
            'type' => 'service',
            'price' => 1_000_000, // ₦10,000
            'is_active' => true,
        ]);

        $discount = DiscountCode::create([
            'code' => 'GEMINI25',
            'percent_off' => 25,
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $res = $this->post(route('service.buy', $product), [
            'account_details' => 'gemini-user@gmail.com',
            'customer_name' => 'Adebayo Tunde',
            'email' => 'adebayo@example.com',
            'phone' => '08012345678',
            'discount_code' => 'gemini25',
            'payment_method' => 'paystack',
        ]);

        $res->assertRedirect('https://checkout.paystack.com/discount-test');

        $order = Order::latest()->firstOrFail();
        $this->assertSame(1_000_000, $order->subtotal); // ₦10,000
        $this->assertSame(250_000, $order->discount_amount); // ₦2,500 saved
        $this->assertSame('GEMINI25', $order->discount_code);
        $this->assertSame(750_000, $order->total); // ₦7,500 paid!
        $this->assertSame(1, $discount->fresh()->uses_count);

        // Verify Paystack was initialized with exactly 750,000 kobo (₦7,500)
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.paystack.co/transaction/initialize'
                && $request['amount'] === 750_000;
        });
    }

    public function test_customer_buys_digital_product_with_discount_code(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/digital-discount'],
            ]),
        ]);

        $cat = Category::create(['name' => 'Digital', 'slug' => 'digital', 'type' => 'product']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'AI Prompt Guide',
            'slug' => 'ai-prompt-guide',
            'type' => 'digital',
            'digital_type' => 'file',
            'price' => 500_000, // ₦5,000
            'is_active' => true,
        ]);

        DiscountCode::create([
            'code' => 'GUIDE20',
            'percent_off' => 20,
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $this->post(route('digital.buy', $product), [
            'customer_name' => 'Chioma Eze',
            'email' => 'chioma@example.com',
            'phone' => '08098765432',
            'discount_code' => 'GUIDE20',
            'payment_method' => 'paystack',
        ])->assertRedirect('https://checkout.paystack.com/digital-discount');

        $order = Order::latest()->firstOrFail();
        $this->assertSame(500_000, $order->subtotal);
        $this->assertSame(100_000, $order->discount_amount); // 20% of 5,000 = 1,000
        $this->assertSame(400_000, $order->total); // ₦4,000
    }

    public function test_admin_can_toggle_and_delete_discount_code(): void
    {
        $admin = $this->admin();
        $discount = DiscountCode::create([
            'code' => 'TOGGLEME',
            'percent_off' => 15,
            'is_active' => true,
        ]);

        // Toggle to inactive
        $this->actingAs($admin)->patch(route('admin.discounts.toggle', $discount));
        $this->assertFalse($discount->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($admin)->patch(route('admin.discounts.toggle', $discount));
        $this->assertTrue($discount->fresh()->is_active);

        // Delete
        $this->actingAs($admin)->delete(route('admin.discounts.destroy', $discount));
        $this->assertDatabaseMissing('discount_codes', ['id' => $discount->id]);
    }

    public function test_discount_check_works_when_product_id_type_differs(): void
    {
        $cat = Category::create(['name' => 'AI', 'slug' => 'ai', 'type' => 'digital']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Chat Gpt',
            'slug' => 'chat-gpt',
            'type' => 'digital',
            'price' => 100_00,
            'is_active' => true,
        ]);

        DiscountCode::create([
            'code' => 'KAMAL',
            'percent_off' => 20,
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        // String ID from MySQL / Request
        $res = $this->postJson(route('discount.check'), [
            'code' => 'KAMAL',
            'product_id' => (string) $product->id,
            'subtotal' => 100_00,
        ]);

        $res->assertOk()
            ->assertJson([
                'valid' => true,
                'code' => 'KAMAL',
                'percent_off' => 20,
                'discount_amount' => 20_00,
                'final_price' => 80_00,
            ]);
    }
}

