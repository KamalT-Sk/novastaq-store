<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $category = Category::whereIn('slug', ['ai-subscriptions-software-services', 'ai-subscriptions', 'software-services'])
            ->orWhere('name', 'like', '%AI%')
            ->first();

        if (! $category) {
            $category = Category::firstOrCreate(
                ['slug' => 'ai-subscriptions-software-services'],
                ['name' => 'AI Subscriptions, Software & Services', 'type' => 'service', 'sort' => 1]
            );
        }

        $categoryId = $category?->id ?? 1;

        // Deactivate all existing products and variants
        Product::query()->update([
            'is_active' => false,
            'is_featured' => false,
        ]);

        ProductVariant::query()->update([
            'is_active' => false,
        ]);

        $saleItems = [
            [
                'name' => 'Coursera Pro',
                'slug' => 'coursera-pro',
                'price' => 2_000_000, // ₦20,000
                'compare_at_price' => 7_500_000, // ₦75,000
                'account_label' => 'Coursera Email Address',
                'short_description' => 'Unlimited access to 7,000+ world-class courses, hands-on projects, and official job-ready professional certificates from Google, Meta, IBM, and top global universities.',
                'description' => "Supercharge your career and master in-demand skills with Coursera Pro.\n\nCoursera Pro unlocks comprehensive, unlimited learning across thousands of industry-standard courses and accredited professional certificate programs with zero per-course fees.\n\n✨ What's Included:\n• 7,000+ Courses & Specializations: Unlimited access to world-renowned courses from Yale, Stanford, Duke, Google, Meta, IBM, AWS, and more.\n• Verified Professional Certificates: Earn shareable, credentialed certificates to add directly to your LinkedIn profile and CV.\n• Hands-On Guided Projects: Practice with pre-configured cloud environments, code editors, and real-world datasets.\n• Flexible Self-Paced Learning: Learn on web, tablet, or mobile with offline downloads.\n\n🎯 Best For & Key Use Cases:\n• Career Switchers & Tech Aspirants: Get certified in Data Analytics, UX/UI Design, Cybersecurity, AI/Machine Learning, Cloud Computing, and Project Management.\n• University Students & Academics: Supplement university studies with accredited global coursework and masterclasses.\n• Professionals & Executives: Sharpen leadership, financial analysis, business strategy, and communication skills.\n\n⚡ How Delivery Works:\nProvide your Coursera email address during checkout. We invite and activate your Coursera Pro membership swiftly, and send instant confirmation via email and WhatsApp.",
                'image' => 'images/products/coursera-pro.svg',
            ],
            [
                'name' => 'ChatGPT Yearly (Shared)',
                'slug' => 'chatgpt-yearly-shared',
                'price' => 2_500_000, // ₦25,000
                'compare_at_price' => 6_000_000, // ₦60,000
                'account_label' => 'WhatsApp / Email for Access Details',
                'short_description' => '1 full year of shared premium ChatGPT access featuring GPT-4o, OpenAI o1 reasoning, Canvas interactive workspace, DALL-E 3 image generation, and custom GPTs.',
                'description' => "Unlock the most powerful AI in the world for an entire year at an unbeatable weekend price.\n\nWith ChatGPT Yearly (Shared), you get premium ChatGPT access powered by OpenAI's cutting-edge reasoning and multimodal models on a stable, managed shared account for 12 uninterrupted months.\n\n✨ What's Included:\n• Advanced Flagship Models: Full access to GPT-4o for lightning-fast answers and OpenAI o1 for deep reasoning, math, and complex logic.\n• Canvas Workspace: Code, write, and iterate side-by-side with AI in an interactive editor.\n• DALL-E 3 Image Generation: Generate photorealistic art, marketing visuals, and diagrams from text prompts.\n• Data Analysis & File Uploads: Upload PDFs, Excel spreadsheets, CSVs, and code files for instant deep analysis and visualizations.\n• Custom GPTs: Access the extensive GPT Store and specialized assistants for research, SEO, coding, and business.\n\n🎯 Best For & Key Use Cases:\n• Software Developers & Engineers: Debug code, refactor algorithms, generate unit tests, and architect systems.\n• Copywriters & Marketers: Write high-converting sales copy, blog posts, social media schedules, and email campaigns.\n• Students & Researchers: Summarize dense academic papers, brainstorm research topics, and solve complex problem sets.\n• Entrepreneurs & Business Owners: Draft business plans, customer support responses, and operational guidelines.\n\n⚡ How Delivery Works:\nComplete your order with your WhatsApp number and email. Dedicated login credentials and access instructions are delivered to you immediately with warranty coverage.",
                'image' => 'images/products/chatgpt-yearly-shared.svg',
            ],
            [
                'name' => 'CapCut Yearly',
                'slug' => 'capcut-yearly',
                'price' => 3_500_000, // ₦35,000
                'compare_at_price' => 8_500_000, // ₦85,000
                'account_label' => 'CapCut / TikTok Account Email',
                'short_description' => '1 full year of CapCut Pro across PC, Mac, and mobile with 4K 60fps export, AI auto-captions, pro transitions, smart cutout, and studio audio.',
                'description' => "Create viral, high-production videos effortlessly with 1 full year of CapCut Pro.\n\nCapCut Pro is the premier editing suite trusted by millions of content creators worldwide, offering pro-level AI editing features, cloud storage, and cinematic templates without any watermarks.\n\n✨ What's Included:\n• Cross-Platform Pro Access: Seamlessly edit on Windows PC, Mac, iPad, iPhone, and Android.\n• 4K 60fps Export: Render crystal-clear, high-bitrate video without compression or watermarks.\n• AI Smart Auto-Captions: Generate dynamic, animated subtitles in seconds with customizable fonts and viral styles.\n• Pro Effects & Transitions: Unlock thousands of premium video filters, 3D zoom effects, kinetic typography, and motion blurs.\n• AI Smart Cutout & Voice Enhancer: Remove backgrounds automatically and eliminate background noise for studio-quality vocal clarity.\n\n🎯 Best For & Key Use Cases:\n• TikTok, Reels & Shorts Creators: Create trending short-form content with auto-sync beats and viral subtitles in minutes.\n• YouTube Content Creators: Edit long-form video, tutorials, and podcasts with multi-track timelines and smooth color grading.\n• E-commerce & Brand Marketers: Produce scroll-stopping product ads, video promotions, and testimonials.\n\n⚡ How Delivery Works:\nProvide your CapCut / TikTok account email at checkout. Pro membership is activated swiftly and backed by guaranteed customer support.",
                'image' => 'images/products/capcut-yearly.svg',
            ],
            [
                'name' => 'Lovable Pro',
                'slug' => 'lovable-pro-12months',
                'price' => 8_500_000, // ₦85,000
                'compare_at_price' => 19_000_000, // ₦190,000
                'account_label' => 'Lovable Account Email',
                'short_description' => 'Full-stack autonomous AI app builder that codes, tests, and deploys real web applications, SaaS dashboards, and databases directly to GitHub.',
                'description' => "Build and launch production-grade software in hours instead of months with Lovable Pro.\n\nLovable is the state-of-the-art AI full-stack developer. You describe what you want in plain English, and Lovable writes clean, modern code, sets up your backend database, connects authentication, and deploys live web apps.\n\n✨ What's Included:\n• 12 Months Lovable Pro Subscription: Substantial monthly AI generation credits and priority server queueing.\n• Full-Stack Application Generation: Creates React, Tailwind CSS, Vite, and TypeScript applications with rich interactive components.\n• Seamless Backend & Database: One-click Supabase database integration, authentication, file storage, and API routing.\n• GitHub Synchronization: Two-way sync with your private GitHub repository—you own 100% of your source code.\n• Custom Domain & Live Hosting: Deploy your web applications instantly to live subdomains or your own custom domain.\n\n🎯 Best For & Key Use Cases:\n• Solo Founders & Indie Hackers: Build and launch SaaS MVPs, client portals, and web tools without hiring an engineering team.\n• Software Developers: 10x development velocity by letting AI scaffold complex frontends, state management, and API integrations.\n• Digital Agencies: Deliver client web apps and prototypes at record speed with minimal turnaround.\n\n⚡ How Delivery Works:\nProvide your email address during checkout. We activate the Pro subscription directly on your Lovable account and send confirmation immediately.",
                'image' => 'images/products/lovable-pro-12months.svg',
            ],
            [
                'name' => 'Grammarly',
                'slug' => 'grammarly',
                'price' => 1_500_000, // ₦15,000
                'compare_at_price' => 4_500_000, // ₦45,000
                'account_label' => 'Grammarly Email Address',
                'short_description' => '1 full year of Grammarly Premium for flawless grammar, advanced tone adjustments, clarity rewrites, and real-time plagiarism detection across all devices.',
                'description' => "Write with complete confidence, authority, and clarity with 1 full year of Grammarly Premium.\n\nGrammarly Premium goes far beyond basic spellcheck. It provides real-time intelligent feedback, full-sentence rewrites, tone refinement, and comprehensive plagiarism detection wherever you type.\n\n✨ What's Included:\n• Full-Sentence Clarity Rewrites: Transform convoluted sentences into crisp, professional prose with one click.\n• Advanced Tone & Style Adjustments: Sound confident, friendly, persuasive, or formal depending on your target audience.\n• Plagiarism Checker: Scan billions of web pages and academic papers to ensure 100% original writing.\n• Vocabulary Enhancement: Suggests sophisticated synonyms and eliminates repetitive phrasing.\n• Universal Device Support: Works seamlessly in Chrome, Edge, Safari, Microsoft Word, Google Docs, Slack, Mac/Windows apps, and mobile keyboards.\n\n🎯 Best For & Key Use Cases:\n• Professionals & Executives: Write flawless emails, client proposals, pitch decks, and corporate reports.\n• Students & Researchers: Polish essays, dissertations, and research papers while ensuring zero plagiarism.\n• Content Creators & Freelancers: Write engaging articles, newsletters, and landing pages that captivate readers.\n\n⚡ How Delivery Works:\nProvide your email address at checkout. We activate your Grammarly Premium access promptly and deliver confirmation straight to your inbox.",
                'image' => 'images/products/grammarly.svg',
            ],
        ];

        foreach ($saleItems as $item) {
            $imagePath = $item['image'];
            unset($item['image']);

            $product = Product::where('slug', $item['slug'])
                ->orWhere('name', $item['name'])
                ->first();

            $data = array_merge($item, [
                'category_id' => $categoryId,
                'type' => 'service',
                'ask_for_price' => false,
                'is_active' => true,
                'is_featured' => true,
                'stock' => null,
            ]);

            if ($product) {
                $product->update($data);
            } else {
                $product = Product::create($data);
            }

            // Deactivate variants to ensure headline price is always used
            ProductVariant::where('product_id', $product->id)->update(['is_active' => false]);

            if ($imagePath) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'path' => $imagePath],
                    ['sort' => 0]
                );
            }
        }
    }

    public function down(): void
    {
        // No down migration needed
    }
};
