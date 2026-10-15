<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        // Find existing ChatGPT Plus product or create it if not present
        $product = Product::where('name', 'like', '%ChatGPT Plus%')
            ->orWhere('slug', 'like', '%chatgpt-plus%')
            ->first();

        if (! $product) {
            $category = Category::where('slug', 'ai-subscriptions')
                ->orWhere('type', 'service')
                ->first();

            if (! $category) {
                $category = Category::create([
                    'name' => 'AI Subscriptions',
                    'slug' => 'ai-subscriptions',
                    'type' => 'service',
                    'sort' => 0,
                ]);
            }

            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'ChatGPT Plus',
                'slug' => 'chatgpt-plus',
                'type' => 'service',
                'price' => 700_000,
                'account_label' => 'ChatGPT Email',
                'short_description' => "OpenAI's popular plan: smarter models, higher message limits, image generation and voice.",
                'description' => "Get more out of ChatGPT with OpenAI's official Plus plan.\n\nChoose from:\n• Shared account monthly (₦7,000)\n• Personal account monthly (₦25,000)\n• Personal account 4 months (₦70,000)\n\nWe activate your subscription and send confirmation to your email and WhatsApp.",
                'is_active' => true,
            ]);
        } else {
            // Update base price to ₦7,000 (lowest variant)
            $product->update([
                'price' => 700_000,
            ]);
        }

        // Tiers requested:
        // 1. Shared account monthly: ₦7,000
        // 2. Personal account monthly: ₦25,000
        // 3. Personal account 4 months: ₦70,000
        $plans = [
            ['name' => 'Shared account monthly', 'price' => 700_000, 'sort' => 1],
            ['name' => 'Personal account monthly', 'price' => 2_500_000, 'sort' => 2],
            ['name' => 'Personal account 4 months', 'price' => 7_000_000, 'sort' => 3],
        ];

        foreach ($plans as $plan) {
            ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'name' => $plan['name']],
                ['price' => $plan['price'], 'sort' => $plan['sort'], 'is_active' => true]
            );
        }
    }

    public function down(): void
    {
        $product = Product::where('name', 'like', '%ChatGPT Plus%')
            ->orWhere('slug', 'like', '%chatgpt-plus%')
            ->first();

        if ($product) {
            ProductVariant::where('product_id', $product->id)->delete();
        }
    }
};
