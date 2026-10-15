<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        // 1. Locate or create the unified category
        $target = Category::where('slug', 'ai-subscriptions-software-services')
            ->orWhere('slug', 'ai-subscriptions')
            ->first();

        if (! $target) {
            $target = Category::create([
                'name' => 'AI Subscriptions, Software & Services',
                'slug' => 'ai-subscriptions-software-services',
                'type' => 'service',
                'sort' => 4,
            ]);
        } else {
            $target->update([
                'name' => 'AI Subscriptions, Software & Services',
                'slug' => 'ai-subscriptions-software-services',
                'type' => 'service',
                'sort' => 4,
            ]);
        }

        // 2. Reassign all products from old categories to the unified category
        $otherCategories = Category::where('id', '!=', $target->id)
            ->whereIn('slug', ['software-services', 'ai-subscriptions', 'software-and-services'])
            ->get();

        foreach ($otherCategories as $oldCat) {
            Product::where('category_id', $oldCat->id)->update(['category_id' => $target->id]);
            $oldCat->delete();
        }

        // 3. Normalize sorting orders for all categories
        $slugOrder = [
            'phones' => 0,
            'laptops' => 1,
            'gadgets-accessories' => 2,
            'office-networking' => 3,
            'ai-subscriptions-software-services' => 4,
        ];

        foreach ($slugOrder as $slug => $sort) {
            Category::where('slug', $slug)->update(['sort' => $sort]);
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback to protect product associations
    }
};
