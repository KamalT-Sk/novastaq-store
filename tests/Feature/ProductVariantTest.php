<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'owner'])->save();

        return $user;
    }

    public function test_product_has_variants_and_calculates_min_max_and_price_display(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'ChatGPT Plus',
            'slug' => 'chatgpt-plus',
            'type' => 'service',
            'price' => 700_000,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Shared account monthly',
            'price' => 700_000, // ₦7,000
            'sort' => 1,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Personal account monthly',
            'price' => 2_500_000, // ₦25,000
            'sort' => 2,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Personal account 4 months',
            'price' => 7_000_000, // ₦70,000
            'sort' => 3,
            'is_active' => true,
        ]);

        $this->assertTrue($product->fresh()->hasVariants());
        $this->assertSame(700_000, $product->fresh()->minPrice());
        $this->assertSame(7_000_000, $product->fresh()->maxPrice());
        $this->assertSame('From ₦7,000', $product->fresh()->priceDisplay());
    }

    public function test_admin_can_create_product_with_variants_and_auto_syncs_lowest_price(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'AI Plans', 'slug' => 'ai-plans', 'type' => 'service']);

        $res = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $cat->id,
            'name' => 'ChatGPT Plus Plans',
            'type' => 'service',
            'is_active' => 1,
            'account_label' => 'ChatGPT Email',
            'has_variants' => '1',
            'variants' => [
                ['name' => 'Shared account monthly', 'price' => '7,000', 'compare_at_price' => ''],
                ['name' => 'Personal account monthly', 'price' => '25,000', 'compare_at_price' => ''],
                ['name' => 'Personal account 4 months', 'price' => '70,000', 'compare_at_price' => '80,000'],
            ],
        ]);

        $res->assertRedirect();
        $product = Product::where('name', 'ChatGPT Plus Plans')->firstOrFail();
        $this->assertSame(700_000, $product->price); // lowest variant ₦7,000
        $this->assertCount(3, $product->variants);

        $v1 = $product->variants()->where('name', 'Shared account monthly')->firstOrFail();
        $this->assertSame(700_000, $v1->price);

        $v2 = $product->variants()->where('name', 'Personal account monthly')->firstOrFail();
        $this->assertSame(2_500_000, $v2->price);

        $v3 = $product->variants()->where('name', 'Personal account 4 months')->firstOrFail();
        $this->assertSame(7_000_000, $v3->price);
        $this->assertSame(8_000_000, $v3->compare_at_price);
    }

    public function test_admin_can_update_variants_and_delete_removed_ones(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'AI Plans', 'slug' => 'ai-plans', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Claude Pro Plans',
            'slug' => 'claude-pro-plans',
            'type' => 'service',
            'price' => 1_000_000,
            'is_active' => true,
        ]);

        $v1 = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Old Tier 1',
            'price' => 1_000_000,
            'sort' => 0,
        ]);
        $v2 = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Old Tier 2',
            'price' => 2_000_000,
            'sort' => 1,
        ]);

        // Update v1, remove v2, and add v3
        $res = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $cat->id,
            'name' => 'Claude Pro Plans',
            'type' => 'service',
            'is_active' => 1,
            'has_variants' => '1',
            'variants' => [
                ['id' => $v1->id, 'name' => 'Updated Tier 1', 'price' => '1,500', 'compare_at_price' => ''],
                ['name' => 'Brand New Tier 3', 'price' => '4,000', 'compare_at_price' => ''],
            ],
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('product_variants', ['id' => $v1->id, 'name' => 'Updated Tier 1', 'price' => 150_000]);
        $this->assertDatabaseMissing('product_variants', ['id' => $v2->id]);
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'name' => 'Brand New Tier 3', 'price' => 400_000]);
        $this->assertSame(150_000, $product->fresh()->price);
    }

    public function test_customer_buys_service_with_specific_variant_records_snapshot(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/variant-service-test'],
            ]),
        ]);

        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'ChatGPT Plus',
            'slug' => 'chatgpt-plus',
            'type' => 'service',
            'price' => 700_000,
            'is_active' => true,
        ]);

        $shared = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Shared account monthly',
            'price' => 700_000,
            'sort' => 1,
        ]);

        $personal = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Personal account monthly',
            'price' => 2_500_000, // ₦25,000
            'sort' => 2,
        ]);

        // Buyer chooses the personal monthly plan (₦25,000)
        $res = $this->post(route('service.buy', $product), [
            'account_details' => 'chatgptuser@gmail.com',
            'customer_name' => 'Emeka Obi',
            'email' => 'emeka@example.com',
            'phone' => '08099887766',
            'variant_id' => $personal->id,
            'payment_method' => 'paystack',
        ]);

        $res->assertRedirect('https://checkout.paystack.com/variant-service-test');

        $order = Order::latest()->firstOrFail();
        $this->assertSame(2_500_000, $order->subtotal);
        $this->assertSame(2_500_000, $order->total);

        $item = $order->items->firstOrFail();
        $this->assertSame($personal->id, $item->product_variant_id);
        $this->assertSame('Personal account monthly', $item->variant_name);
        $this->assertSame(2_500_000, $item->unit_price);
        $this->assertSame('ChatGPT Plus (Personal account monthly)', $item->displayName());

        Http::assertSent(fn ($req) => $req['amount'] === 2_500_000);
    }

    public function test_customer_buys_digital_product_with_variant(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/variant-digital-test'],
            ]),
        ]);

        $cat = Category::create(['name' => 'Digital Goods', 'slug' => 'digital', 'type' => 'product']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Software License Key',
            'slug' => 'software-license-key',
            'type' => 'digital',
            'digital_type' => 'manual',
            'price' => 500_000,
            'is_active' => true,
        ]);

        $v1 = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 Device / 1 Year',
            'price' => 500_000,
            'sort' => 1,
        ]);

        $v2 = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '5 Devices / Lifetime',
            'price' => 1_800_000,
            'sort' => 2,
        ]);

        $res = $this->post(route('digital.buy', $product), [
            'customer_name' => 'Chidi Okeke',
            'email' => 'chidi@example.com',
            'phone' => '08011223344',
            'variant_id' => $v2->id,
            'payment_method' => 'paystack',
        ]);

        $res->assertRedirect('https://checkout.paystack.com/variant-digital-test');

        $order = Order::latest()->firstOrFail();
        $this->assertSame(1_800_000, $order->total);
        $item = $order->items->firstOrFail();
        $this->assertSame($v2->id, $item->product_variant_id);
        $this->assertSame('5 Devices / Lifetime', $item->variant_name);
        $this->assertSame('Software License Key (5 Devices / Lifetime)', $item->displayName());
    }

    public function test_discount_code_applies_correctly_to_selected_variant(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/variant-discount-test'],
            ]),
        ]);

        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'ChatGPT Plus',
            'slug' => 'chatgpt-plus',
            'type' => 'service',
            'price' => 700_000,
            'is_active' => true,
        ]);

        $personal4m = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Personal account 4 months',
            'price' => 7_000_000, // ₦70,000
            'sort' => 3,
        ]);

        // 10% discount on ChatGPT Plus
        DiscountCode::create([
            'code' => 'PLUS10',
            'percent_off' => 10,
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        // Test API check endpoint with variant_id
        $checkRes = $this->postJson(route('discount.check'), [
            'code' => 'plus10',
            'product_id' => $product->id,
            'variant_id' => $personal4m->id,
            'subtotal' => 7_000_000,
        ]);

        $checkRes->assertOk();
        $checkRes->assertJson([
            'valid' => true,
            'percent_off' => 10,
            'discount_amount' => 700_000, // 10% of 70,000 is 7,000
            'final_price' => 6_300_000,   // 63,000
        ]);

        // Purchase with variant and discount
        $res = $this->post(route('service.buy', $product), [
            'account_details' => 'chatgptuser@gmail.com',
            'customer_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '08012345678',
            'variant_id' => $personal4m->id,
            'discount_code' => 'plus10',
            'payment_method' => 'paystack',
        ]);

        $res->assertRedirect('https://checkout.paystack.com/variant-discount-test');

        $order = Order::latest()->firstOrFail();
        $this->assertSame(7_000_000, $order->subtotal);
        $this->assertSame(700_000, $order->discount_amount);
        $this->assertSame(6_300_000, $order->total); // ₦63,000

        Http::assertSent(fn ($req) => $req['amount'] === 6_300_000);
    }

    public function test_storefront_displays_from_price_and_all_variant_options(): void
    {
        $cat = Category::create(['name' => 'AI Subscriptions', 'slug' => 'ai', 'type' => 'service']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'ChatGPT Plus',
            'slug' => 'chatgpt-plus',
            'type' => 'service',
            'price' => 700_000,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Shared account monthly',
            'price' => 700_000,
            'sort' => 1,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Personal account monthly',
            'price' => 2_500_000,
            'sort' => 2,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Personal account 4 months',
            'price' => 7_000_000,
            'sort' => 3,
        ]);

        $res = $this->get(route('product', $product));

        $res->assertOk();
        $res->assertSee('From ₦7,000');
        $res->assertSee('Shared account monthly');
        $res->assertSee('Personal account monthly');
        $res->assertSee('Personal account 4 months');
        $res->assertSee('₦25,000');
        $res->assertSee('₦70,000');
    }
}
