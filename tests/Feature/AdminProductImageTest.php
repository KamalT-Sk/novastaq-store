<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductImageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'owner'])->save();

        return $user;
    }

    private function createProduct(): Product
    {
        $cat = Category::create(['name' => 'AI Apps', 'slug' => 'ai-apps', 'type' => 'digital']);

        return Product::create([
            'category_id' => $cat->id,
            'name' => 'Gemini Advanced',
            'slug' => 'gemini-advanced',
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => 1_000_000,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_upload_images_directly(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->createProduct();

        $image1 = UploadedFile::fake()->image('gemini-1.png');
        $image2 = UploadedFile::fake()->image('gemini-2.jpg');

        $res = $this->actingAs($admin)->post(route('admin.products.images.store', $product), [
            'images' => [$image1, $image2],
        ]);

        $res->assertSessionHas('status');
        $this->assertCount(2, $product->fresh()->images);
        $this->assertEquals(1, $product->fresh()->images->first()->sort);
        $this->assertEquals(2, $product->fresh()->images->last()->sort);
    }

    public function test_admin_can_delete_individual_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->createProduct();

        $img = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/test-image.png',
            'sort' => 1,
        ]);

        $res = $this->actingAs($admin)->delete(route('admin.products.images.destroy', [$product, $img]));

        $res->assertSessionHas('status');
        $this->assertDatabaseMissing('product_images', ['id' => $img->id]);
    }

    public function test_admin_can_batch_delete_marked_images(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->createProduct();

        $img1 = ProductImage::create(['product_id' => $product->id, 'path' => 'products/img-1.png', 'sort' => 1]);
        $img2 = ProductImage::create(['product_id' => $product->id, 'path' => 'products/img-2.png', 'sort' => 2]);
        $img3 = ProductImage::create(['product_id' => $product->id, 'path' => 'products/img-3.png', 'sort' => 3]);

        $res = $this->actingAs($admin)->delete(route('admin.products.images.destroy-batch', $product), [
            'remove_images' => [$img1->id, $img3->id],
        ]);

        $res->assertSessionHas('status');
        $this->assertDatabaseMissing('product_images', ['id' => $img1->id]);
        $this->assertDatabaseHas('product_images', ['id' => $img2->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $img3->id]);
    }

    public function test_cannot_delete_image_belonging_to_another_product(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product1 = $this->createProduct();
        $cat = Category::first();
        $product2 = Product::create([
            'category_id' => $cat->id,
            'name' => 'ChatGPT Plus',
            'slug' => 'chatgpt-plus',
            'type' => 'digital',
            'digital_type' => 'pool',
            'price' => 1_000_000,
            'is_active' => true,
        ]);

        $img = ProductImage::create([
            'product_id' => $product2->id,
            'path' => 'products/chatgpt.png',
            'sort' => 1,
        ]);

        $res = $this->actingAs($admin)->delete(route('admin.products.images.destroy', [$product1, $img]));

        $res->assertNotFound();
        $this->assertDatabaseHas('product_images', ['id' => $img->id]);
    }
}

