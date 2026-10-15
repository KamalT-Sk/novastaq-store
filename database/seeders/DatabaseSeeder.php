<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Starter categories; rename or add more in the admin. Safe to run again. */
    public function run(): void
    {
        $categories = [
            ['Phones', 'product'],
            ['Laptops', 'product'],
            ['Gadgets & Accessories', 'product'],
            ['Office & Networking', 'product'],
            ['AI Subscriptions, Software & Services', 'service'],
        ];

        foreach ($categories as $i => [$name, $type]) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'type' => $type, 'sort' => $i]);
        }
    }
}
