<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $unifiedCat = Category::whereIn('slug', ['ai-subscriptions-software-services', 'ai-subscriptions', 'software-services'])->first() ?? Category::first();
        $aiCat = $unifiedCat;
        $softCat = $unifiedCat;

        $items = [
            [
                'name' => 'Factory 12months',
                'slug' => 'factory-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 1,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => true,
                'short_description' => 'Enterprise autonomous Droid AI software engineer subscription for 12 months.',
                'description' => "Factory provides autonomous AI software engineers (Droids) designed to integrate directly into your engineering workflows. Handle code generation, automated migrations, documentation, unit test writing, and pull request reviews at scale with private enterprise AI infrastructure.\n\n• 12 Months dedicated subscription\n• Autonomous AI Droids for coding and code review\n• Private, secure and scalable development engine\n• Guaranteed fast activation on your email account",
                'image' => 'images/products/factory-12months.svg',
            ],
            [
                'name' => 'Gumloop Pro 12months',
                'slug' => 'gumloop-pro-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 1,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'AI-powered browser automation and complex workflow execution for 12 months.',
                'description' => "Gumloop enables individuals and teams to build and deploy autonomous AI web scraping, workflow orchestration, and task automation pipelines without writing boilerplate code.\n\n• Pro Plan with 12 months validity\n• High-concurrency workflow runs and automated web scrapers\n• Native integration with web tools, spreadsheets, and databases\n• Setup and instant account activation directly on your email",
                'image' => 'images/products/gumloop-pro-12months.svg',
            ],
            [
                'name' => 'Magic Patterns Starter 12months',
                'slug' => 'magic-patterns-starter-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 1,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'AI prototyping tool that transforms ideas into production-ready UI components.',
                'description' => "Magic Patterns is an AI design environment that turns text prompts and wireframes into clean, interactive React, Tailwind, and Figma-compatible components in seconds.\n\n• Starter Plan for 12 months\n• Generate interactive UI prototypes and design system patterns\n• Instant export to React, Vue, HTML, and Figma\n• Direct activation on your email account",
                'image' => 'images/products/magic-patterns-starter-12months.svg',
            ],
            [
                'name' => 'Manus Pro 12months',
                'slug' => 'manus-pro-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 1,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => true,
                'short_description' => 'Next-generation general autonomous AI agent for complex digital tasks.',
                'description' => "Manus AI is a pioneering autonomous agent designed to solve complex multi-step digital work across the web, documents, codebases, and deep analytical research.\n\n• Pro Plan for 12 full months\n• High-priority agent execution and advanced reasoning models\n• Multi-tool web navigation, file processing, and task completion\n• Priority concierge setup on your email account",
                'image' => 'images/products/manus-pro-12months.svg',
            ],
            [
                'name' => 'Supercut Pro 10 Seat 12months',
                'slug' => 'supercut-pro-10-seat-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 1,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'Team AI video workspace with automated silence removal, cuts, and captioning.',
                'description' => "Supercut Pro revolutionizes multi-creator video production with AI-powered smart trimming, automated silence and filler removal, viral clip generation, and dynamic subtitles.\n\n• 10-Seat Team License for 12 months\n• Unlimited AI video cut generation and smart editing\n• Studio-grade audio cleaning and dynamic typography subtitles\n• Fast activation and team management support",
                'image' => 'images/products/supercut-pro-10-seat-12months.svg',
            ],
            [
                'name' => 'Framer Pro 12months',
                'slug' => 'framer-pro-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 2,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => true,
                'short_description' => 'The ultimate web design platform with AI generation, custom domains, and zero code.',
                'description' => "Framer Pro empowers designers and founders to design, animate, and publish blazing-fast websites with interactive canvas layouts, CMS collections, and responsive breakpoint controls.\n\n• Framer Pro plan for 12 months\n• Custom domains, high bandwidth, and analytics\n• Full CMS capabilities, staging environments, and localization\n• Activated securely on your email account",
                'image' => 'images/products/framer-pro-12months.svg',
            ],
            [
                'name' => 'Gamma Pro 12months',
                'slug' => 'gamma-pro-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 2,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'AI-powered presentation, document, and webpage generator with custom branding.',
                'description' => "Gamma App generates polished decks, interactive business proposals, and responsive documents in seconds with advanced AI writing and layout styling.\n\n• Gamma Pro subscription for 12 months\n• Unlimited AI generation credits and custom themes\n• Custom fonts, advanced export options (PDF/PPTX), and analytics\n• Direct activation on your email account",
                'image' => 'images/products/gamma-pro-12months.svg',
            ],
            [
                'name' => 'Lovable Pro 12months',
                'slug' => 'lovable-pro-12months',
                'category_id' => $aiCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 2,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => true,
                'short_description' => 'Full-stack autonomous AI app builder that creates real software from prompts.',
                'description' => "Lovable is the cutting-edge AI software engineer that codes, tests, and deploys full-stack web applications, dashboards, and SaaS products directly into GitHub.\n\n• Lovable Pro tier for 12 months\n• High AI prompt quotas and priority processing\n• GitHub repository sync, custom domains, and database integrations\n• Activated directly on your email account",
                'image' => 'images/products/lovable-pro-12months.svg',
            ],
            [
                'name' => 'Resend Pro 12months',
                'slug' => 'resend-pro-12months',
                'category_id' => $softCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 181,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'Modern developer email API platform with superior deliverability and analytics.',
                'description' => "Resend delivers transactional and marketing emails for modern applications with React Email templates, dedicated IP options, and deep deliverability tracking.\n\n• Resend Pro plan for 12 months\n• High sending quotas, webhooks, and domain verification\n• Exceptional inbox delivery rates and real-time event analytics\n• Immediate account activation on your email",
                'image' => 'images/products/resend-pro-12months.svg',
            ],
            [
                'name' => 'Adobe Express 12months',
                'slug' => 'adobe-express-12months',
                'category_id' => $softCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 216,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'All-in-one AI creative suite powered by Adobe Firefly generative models.',
                'description' => "Adobe Express brings the power of Adobe Photoshop, Illustrator, and Premiere into an easy-to-use browser and mobile app powered by Adobe Firefly AI.\n\n• 12 Months Premium subscription\n• Generative Fill, Text to Image, and one-click background removal\n• Millions of royalty-free Adobe Stock assets, templates, and fonts\n• Activated on your email account",
                'image' => 'images/products/adobe-express-12months.svg',
            ],
            [
                'name' => 'Apple Music 5months',
                'slug' => 'apple-music-5months',
                'category_id' => $softCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 239,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => true,
                'short_description' => 'Over 100 million songs in Lossless Audio with Spatial Dolby Atmos.',
                'description' => "Apple Music provides unlimited ad-free streaming of 100+ million songs, studio-quality Lossless and Hi-Res Lossless audio, immersive Spatial Audio with Dolby Atmos, and curated editorial playlists.\n\n• 5 Months uninterrupted subscription\n• Offline downloads and sync across iPhone, Android, Mac, and PC\n• Full access to Apple Music Classical and live radio\n• Activated securely on your Apple ID email",
                'image' => 'images/products/apple-music-5months.svg',
            ],
            [
                'name' => 'Linear Business 12months',
                'slug' => 'linear-business-12months',
                'category_id' => $softCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 285,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'Purpose-built tool for high-performance product teams and issue management.',
                'description' => "Linear is the industry-standard software project management system built for speed, keyboard shortcuts, cycles, roadmaps, and seamless GitHub/GitLab workflows.\n\n• Linear Business subscription for 12 months\n• Unlimited history, roadmaps, customer requests, and guest accounts\n• Advanced analytics, SLA tracking, and priority support\n• Activated on your business or personal email account",
                'image' => 'images/products/linear-business-12months.svg',
            ],
            [
                'name' => 'Prime',
                'slug' => 'prime',
                'category_id' => $softCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 4,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'Amazon Prime subscription including Prime Video streaming and gaming perks.',
                'description' => "Amazon Prime unlocks thousands of award-winning movies and series on Prime Video in 4K HDR, Prime Gaming free games and Twitch subscriptions, and exclusive member discounts.\n\n• Full Amazon Prime subscription\n• 4K HDR streaming with multi-profile and multi-device support\n• Offline downloads on phone, tablet, and smart TV\n• Fast activation directly on your email account",
                'image' => 'images/products/prime.svg',
            ],
            [
                'name' => 'Spotify',
                'slug' => 'spotify',
                'category_id' => $softCat?->id,
                'type' => 'service',
                'account_label' => 'Email Account',
                'stock' => 92,
                'ask_for_price' => true,
                'price' => 0,
                'is_active' => true,
                'is_featured' => false,
                'short_description' => 'Spotify Premium for ad-free music, offline listening, and maximum audio quality.',
                'description' => "Spotify Premium lets you play any song, anywhere, on any device without commercial interruptions. Enjoy unlimited skips, offline song downloads, and high-fidelity 320kbps audio.\n\n• Spotify Premium access\n• 100% Ad-free music and podcast playback\n• Download playlists and albums for offline listening\n• Activated smoothly on your Spotify email account",
                'image' => 'images/products/spotify.svg',
            ],
        ];

        foreach ($items as $item) {
            $imagePath = $item['image'];
            unset($item['image']);

            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );

            if ($imagePath && ! $product->images()->where('path', $imagePath)->exists()) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $imagePath,
                    'sort' => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'factory-12months', 'gumloop-pro-12months', 'magic-patterns-starter-12months',
            'manus-pro-12months', 'supercut-pro-10-seat-12months', 'framer-pro-12months',
            'gamma-pro-12months', 'lovable-pro-12months', 'resend-pro-12months',
            'adobe-express-12months', 'apple-music-5months', 'linear-business-12months',
            'prime', 'spotify',
        ];

        Product::whereIn('slug', $slugs)->delete();
    }
};
