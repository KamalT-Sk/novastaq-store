<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\DigitalKeyImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function product(): Product
    {
        $category = Category::firstOrCreate(['slug' => 'digital'], ['name' => 'Digital', 'type' => 'product']);

        return Product::create(['category_id' => $category->id, 'name' => 'License', 'slug' => 'license-'.Product::count(),
            'type' => 'digital', 'digital_type' => 'pool', 'price' => 100000, 'is_active' => true]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'owner'])->save();

        return $admin;
    }

    private function order(Product $product, ?User $user = null): Order
    {
        $order = Order::create(['user_id' => $user?->id, 'customer_name' => 'Buyer', 'email' => 'buyer@example.com',
            'phone' => '080', 'subtotal' => 100000, 'total' => 100000, 'payment_status' => 'paid', 'status' => 'completed']);
        $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'unit_price' => 100000,
            'quantity' => 1, 'line_total' => 100000, 'delivery_type' => 'digital', 'digital_file' => 'digital/original.pdf']);

        return $order;
    }

    public function test_edit_and_delivery_preserve_exact_credentials(): void
    {
        $product = $this->product();
        $value = 'https://example.com/ABC2.';
        $key = $product->digitalKeys()->create(['key_content' => $value]);
        $item = $this->order($product)->items()->first();
        $item->update(['digital_content' => $value]);
        $this->actingAs($this->admin())->get(route('admin.products.edit', $product))->assertOk();
        $this->assertSame($value, $key->fresh()->getRawOriginal('key_content'));
        $this->assertSame([$value], $item->fresh()->digitalLinks());
        $this->assertSame(['ABC2.', '[123]', '0'], DigitalKeyImport::parse("ABC2.\n[123]\n0"));
        $this->assertSame(['https://example.com/ABC2.'], DigitalKeyImport::parse('1. https://example.com/ABC2.', 'list'));
    }

    public function test_get_cannot_delete_and_nested_key_must_belong_to_product(): void
    {
        $product = $this->product();
        $other = $this->product();
        $key = $other->digitalKeys()->create(['key_content' => 'CODE']);
        $this->actingAs($this->admin())->get(route('admin.digital-keys.destroy', $key))->assertStatus(405);
        $this->deleteJson(route('admin.products.keys.destroy', [$product, $key]))->assertNotFound();
        $this->assertModelExists($key);
    }

    public function test_delivered_keys_cannot_be_removed_or_reused_even_if_order_reference_is_cleared(): void
    {
        $product = $this->product();
        $key = $product->digitalKeys()->create(['key_content' => 'DELIVERED', 'assigned_at' => now()]);
        $this->actingAs($this->admin())->deleteJson(route('admin.digital-keys.destroy', $key))->assertStatus(422);
        $this->assertSame(0, $product->availableDigitalKeysCount());
    }

    public function test_delete_is_idempotent(): void
    {
        $product = $this->product();
        $key = $product->digitalKeys()->create(['key_content' => 'CODE']);
        $url = route('admin.products.keys.destroy', [$product, $key]);
        $this->actingAs($this->admin())->deleteJson($url)->assertOk();
        $this->deleteJson($url)->assertOk()->assertJsonPath('already_removed', true);
    }

    public function test_preview_and_import_skip_duplicates_and_preserve_html_as_text(): void
    {
        $product = $this->product();
        $product->digitalKeys()->create(['key_content' => 'EXISTING']);
        $payload = ['add_digital_keys' => "EXISTING\nNEW2.\nNEW2.\n<img src=x onerror=alert(1)>"];
        $this->actingAs($this->admin())->postJson(route('admin.products.keys.preview', $product), $payload)
            ->assertOk()->assertJsonCount(2, 'keys')->assertJsonCount(2, 'duplicates');
        $this->postJson(route('admin.products.keys.store', $product), $payload)->assertOk()->assertJsonPath('count', 2);
        $this->postJson(route('admin.products.keys.store', $product), $payload)->assertOk()->assertJsonPath('count', 0);
        $this->assertSame(3, $product->digitalKeys()->count());
        $this->get(route('admin.products.edit', $product))->assertOk()->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_inventory_is_paginated_and_searchable(): void
    {
        $product = $this->product();
        for ($i = 0; $i < 31; $i++) {
            $product->digitalKeys()->create(['key_content' => 'CODE-'.$i]);
        }
        $this->actingAs($this->admin())->getJson(route('admin.products.keys.index', $product))
            ->assertOk()->assertJsonCount(25, 'keys.data')->assertJsonPath('keys.total', 31);
        $this->getJson(route('admin.products.keys.index', [$product, 'q' => 'CODE-30']))
            ->assertOk()->assertJsonCount(1, 'keys.data');
    }

    public function test_private_download_requires_owner_session_or_signed_link(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('digital/original.pdf', 'paid content');
        $owner = User::factory()->create();
        $order = $this->order($this->product(), $owner);
        $item = $order->items()->first();
        $url = route('orders.download', [$order->number, $item->id]);
        $this->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk()->assertDownload();
        auth()->forgetGuards();
        $this->get($item->downloadUrl())->assertOk()->assertDownload();
        $expired = URL::temporarySignedRoute('orders.download', now()->subMinute(), [$order->number, $item->id]);
        $this->get($expired)->assertForbidden();
        $order->update(['payment_status' => 'unpaid']);
        $this->get($item->downloadUrl())->assertForbidden();
    }

    public function test_order_pages_cannot_be_used_to_bypass_download_authorization(): void
    {
        $order = $this->order($this->product());
        $this->get(route('order.complete', $order->number))->assertForbidden();
        $this->get(route('checkout.callback', ['reference' => $order->number]))->assertForbidden();
        $this->withSession(['order_access' => [$order->id => true]])->get(route('order.complete', $order->number))->assertOk();
    }

    public function test_replacement_wins_over_remove_and_old_order_file_survives(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('digital/original.pdf', 'original');
        $product = $this->product();
        $product->update(['digital_type' => 'file', 'digital_file' => 'digital/original.pdf']);
        $order = $this->order($product);
        $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'name' => 'License', 'type' => 'digital', 'digital_type' => 'file', 'category_id' => $product->category_id,
            'price' => '1000', 'digital_file' => UploadedFile::fake()->createWithContent('new.pdf', "%PDF-1.4\nreplacement"),
            'remove_digital_file' => 1, 'save_action' => 'continue',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.products.edit', $product));
        $this->assertNotNull($product->fresh()->digital_file);
        $this->assertNotSame('digital/original.pdf', $product->fresh()->digital_file);
        Storage::disk('local')->assertExists('digital/original.pdf');
        Storage::disk('local')->assertExists($product->fresh()->digital_file);
        $this->assertSame('digital/original.pdf', $order->items()->first()->digital_file);
    }

    public function test_failed_save_rolls_back_product_and_cleans_new_uploads(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $product = $this->product();
        ProductImage::creating(fn () => throw new \RuntimeException('Simulated image failure'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
                'name' => 'Changed', 'type' => 'digital', 'category_id' => $product->category_id, 'price' => '1000',
                'digital_file' => UploadedFile::fake()->createWithContent('new.pdf', "%PDF-1.4\nreplacement"),
                'images' => [UploadedFile::fake()->image('cover.png')],
            ]);
            $this->fail('Expected simulated failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated image failure', $e->getMessage());
        } finally {
            ProductImage::flushEventListeners();
        }
        $this->assertSame('License', $product->fresh()->name);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_existing_public_files_are_verified_and_moved_privately(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $product = $this->product();
        $product->update(['digital_file' => 'digital/original.pdf']);
        Storage::disk('public')->put('digital/original.pdf', 'original');
        $this->artisan('store:secure-digital-files')->assertSuccessful();
        Storage::disk('public')->assertMissing('digital/original.pdf');
        $this->assertSame('original', Storage::disk('local')->get('digital/original.pdf'));
        $this->artisan('store:secure-digital-files')->assertSuccessful();
    }
}
