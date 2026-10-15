<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_unified_category(): void
    {
        $this->seed(DatabaseSeeder::class);

        $categories = Category::orderBy('sort')->get();
        $this->assertCount(5, $categories);

        $unified = Category::where('slug', 'ai-subscriptions-software-services')->first();
        $this->assertNotNull($unified);
        $this->assertSame('AI Subscriptions, Software & Services', $unified->name);
        $this->assertSame('service', $unified->type);
    }

    public function test_legacy_slugs_resolve_to_unified_category(): void
    {
        $unified = Category::create([
            'name' => 'AI Subscriptions, Software & Services',
            'slug' => 'ai-subscriptions-software-services',
            'type' => 'service',
            'sort' => 4,
        ]);

        $product = Product::create([
            'category_id' => $unified->id,
            'name' => 'ChatGPT Plus',
            'slug' => 'chatgpt-plus',
            'type' => 'service',
            'price' => 700_000,
            'is_active' => true,
        ]);

        // Access via canonical slug
        $this->get('/c/ai-subscriptions-software-services')->assertOk()
            ->assertSee('AI Subscriptions, Software & Services')
            ->assertSee('ChatGPT Plus');

        // Access via legacy ai-subscriptions slug
        $this->get('/c/ai-subscriptions')->assertOk()
            ->assertSee('AI Subscriptions, Software & Services')
            ->assertSee('ChatGPT Plus');

        // Access via legacy software-services slug
        $this->get('/c/software-services')->assertOk()
            ->assertSee('AI Subscriptions, Software & Services')
            ->assertSee('ChatGPT Plus');
    }

    public function test_both_ai_and_software_products_render_in_unified_category(): void
    {
        $cat = Category::create([
            'name' => 'AI Subscriptions, Software & Services',
            'slug' => 'ai-subscriptions-software-services',
            'type' => 'service',
            'sort' => 4,
        ]);

        Product::create([
            'category_id' => $cat->id,
            'name' => 'Manus Pro 12months',
            'slug' => 'manus-pro-12months',
            'type' => 'service',
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $cat->id,
            'name' => 'Spotify',
            'slug' => 'spotify',
            'type' => 'service',
            'ask_for_price' => true,
            'price' => 0,
            'is_active' => true,
        ]);

        $res = $this->get(route('category', $cat));
        $res->assertOk();
        $res->assertSee('Manus Pro 12months');
        $res->assertSee('Spotify');
        $res->assertSee('Ask for price');
    }
}

