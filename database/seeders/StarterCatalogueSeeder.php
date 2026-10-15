<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Popular products to start the store with. Every product is created HIDDEN with no price
 * (price 0 shows "Needs price" in the admin): add the price and photos, then switch it Live.
 * Safe to run again: products whose web address already exists are skipped, so edits are kept.
 * Run from Admin → Products → "Add starter catalogue".
 */
class StarterCatalogueSeeder extends Seeder
{
    public int $created = 0;

    public function run(): void
    {
        foreach (self::catalogue() as $categoryName => [$type, $items]) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'type' => $type === 'service' ? 'service' : 'product', 'sort' => Category::max('sort') + 1],
            );

            foreach ($items as $item) {
                [$name, $short, $long] = $item;
                $slug = Str::slug($name);
                if (Product::where('slug', $slug)->exists() || Product::where('name', $name)->exists()) {
                    continue;
                }

                Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => $slug,
                    'type' => $type,
                    'account_label' => $item[3] ?? null,
                    'short_description' => $short,
                    'description' => $long,
                    'price' => 0,
                    'stock' => null,
                    'is_active' => false,
                    'is_featured' => false,
                ]);
                $this->created++;
            }
        }
    }

    /** @return array<string, array{0: string, 1: list<array>}> category => [type, [name, short, long, account label?]] */
    public static function catalogue(): array
    {
        return [
            'Phones' => ['physical', [
                ['iPhone 17 Pro Max', '6.9" ProMotion display, A19 Pro chip and three 48MP cameras with 8x optical-quality zoom.',
                    "Apple's biggest and most capable iPhone.\n\n• 6.9-inch Super Retina XDR OLED display with ProMotion up to 120Hz and up to 3,000 nits outdoors\n• A19 Pro chip for fast performance, gaming and Apple Intelligence\n• Three 48MP Fusion cameras: Main, Ultra Wide and Telephoto with 8x optical-quality zoom\n• 18MP Center Stage front camera\n• All-day battery: up to 37 hours of video playback\n• USB-C with fast charging\n\nGenuine, brand new. Choose your storage and colour when you order."],
                ['iPhone 17 Pro', 'Pro performance in a 6.3" size: A19 Pro chip and triple 48MP cameras.',
                    "The Pro iPhone in a more compact size.\n\n• 6.3-inch Super Retina XDR OLED display with ProMotion up to 120Hz\n• A19 Pro chip\n• Three 48MP Fusion cameras with 8x optical-quality zoom\n• 18MP Center Stage front camera\n• USB-C with fast charging\n\nGenuine, brand new."],
                ['iPhone 17', 'The everyday iPhone with a 120Hz ProMotion display and 48MP cameras.',
                    "The standard iPhone, now with a smoother display.\n\n• 6.3-inch Super Retina XDR display with ProMotion\n• A19 chip with Apple Intelligence\n• 48MP Fusion main camera and 48MP Ultra Wide\n• 18MP Center Stage front camera\n• USB-C\n\nGenuine, brand new."],
                ['iPhone 16 Pro Max', '6.9" display, A18 Pro chip, 5x telephoto and Camera Control.',
                    "Last year's flagship, still one of the best phones you can buy.\n\n• 6.9-inch Super Retina XDR display with ProMotion and Always-On\n• A18 Pro chip with Apple Intelligence\n• 48MP Fusion camera, 48MP Ultra Wide and 5x Telephoto\n• Camera Control button and 4K 120fps Dolby Vision video\n• Titanium design, USB-C\n\nGenuine, brand new or certified UK-used: ask us."],
                ['iPhone 16', '6.1" display, A18 chip, Camera Control and Action button.',
                    "A great all-round iPhone.\n\n• 6.1-inch Super Retina XDR display\n• A18 chip with Apple Intelligence\n• 48MP Fusion camera and 12MP Ultra Wide\n• Camera Control and Action button\n• USB-C\n\nGenuine, brand new."],
                ['iPhone 15', '6.1" display, Dynamic Island, 48MP camera and USB-C.',
                    "Excellent value iPhone.\n\n• 6.1-inch Super Retina XDR display with Dynamic Island\n• A16 Bionic chip\n• 48MP main camera with 2x telephoto option\n• USB-C\n\nGenuine, brand new."],
                ['Samsung Galaxy S25 Ultra', '6.9" AMOLED, Snapdragon 8 Elite for Galaxy, 200MP camera and built-in S Pen.',
                    "Samsung's top flagship.\n\n• 6.9-inch Dynamic AMOLED 2X display, 120Hz\n• Snapdragon 8 Elite for Galaxy\n• 200MP main camera, 50MP ultra wide, 3x and 5x telephoto\n• Built-in S Pen and Galaxy AI features\n• 5,000mAh battery with fast charging\n• Titanium frame\n\nGenuine, brand new."],
                ['Samsung Galaxy S25', 'Compact flagship: 6.2" AMOLED, Snapdragon 8 Elite for Galaxy, Galaxy AI.',
                    "Flagship power in a smaller phone.\n\n• 6.2-inch Dynamic AMOLED 2X display, 120Hz\n• Snapdragon 8 Elite for Galaxy\n• 50MP main, 12MP ultra wide and 10MP 3x telephoto cameras\n• Galaxy AI features\n\nGenuine, brand new."],
                ['Samsung Galaxy A16', 'Affordable Samsung with a large 6.7" Super AMOLED display and long software support.',
                    "A reliable everyday Samsung at a friendly price.\n\n• 6.7-inch Super AMOLED display, 90Hz\n• 50MP main camera\n• 5,000mAh battery with 25W fast charging\n• Long-term Android and security updates\n\nGenuine, brand new."],
                ['Google Pixel 9', 'Pure Android with Google AI, a brilliant camera and 7 years of updates.',
                    "Google's own phone, known for its camera.\n\n• 6.3-inch Actua OLED display, 120Hz\n• Google Tensor G4 chip with Gemini built in\n• 50MP main and 48MP ultra wide cameras\n• 7 years of Android and security updates\n\nGenuine, brand new."],
                ['Tecno Camon 30 Pro', 'Stylish mid-range phone with a 50MP selfie camera and AMOLED display.',
                    "Popular mid-ranger for photos and selfies.\n\n• 6.78-inch AMOLED display, 144Hz\n• 50MP main camera and 50MP front camera\n• 5,000mAh battery with fast charging\n\nGenuine, brand new."],
                ['Infinix Note 40 Pro', 'Big AMOLED screen, fast charging and wireless magnetic charging at a mid-range price.',
                    "Lots of phone for the money.\n\n• 6.78-inch curved AMOLED display, 120Hz\n• 108MP main camera\n• 5,000mAh battery with fast wired and magnetic wireless charging\n\nGenuine, brand new."],
                ['Redmi Note 13 Pro', '200MP camera, 120Hz AMOLED and fast charging from Xiaomi.',
                    "A best-seller in the mid-range.\n\n• 6.67-inch AMOLED display, 120Hz\n• 200MP main camera\n• 5,000mAh battery with fast charging\n\nGenuine, brand new."],
            ]],

            'Laptops' => ['physical', [
                ['MacBook Air 13" M4', 'Light, silent and fast: Apple M4, 16GB memory and up to 18 hours of battery.',
                    "The best laptop for most people.\n\n• 13.6-inch Liquid Retina display\n• Apple M4 chip with 10-core CPU\n• 16GB unified memory (configurable up to 32GB), SSD from 256GB\n• Up to 18 hours of battery life\n• Fanless and silent, 12MP Center Stage camera\n• Two Thunderbolt ports, MagSafe and headphone jack\n\nGenuine, brand new. Ask about memory and storage options."],
                ['MacBook Air 15" M4', 'The big-screen MacBook Air: 15.3" display, Apple M4 and all-day battery.',
                    "More screen, same light design.\n\n• 15.3-inch Liquid Retina display\n• Apple M4 chip, 16GB unified memory\n• Up to 18 hours of battery life\n• Six-speaker sound system\n\nGenuine, brand new."],
                ['MacBook Pro 14" M4', 'Pro laptop with a Liquid Retina XDR display, Apple M4 and up to 24 hours of battery.',
                    "For creatives and developers.\n\n• 14.2-inch Liquid Retina XDR display, up to 120Hz\n• Apple M4 chip (M4 Pro and M4 Max also available)\n• Up to 24 hours of battery life\n• HDMI, SD card slot, Thunderbolt and MagSafe\n\nGenuine, brand new."],
                ['Dell XPS 13', 'Premium ultraportable with an edge-to-edge display and all-day battery.',
                    "Dell's flagship small laptop.\n\n• 13.4-inch InfinityEdge display\n• Latest Intel Core Ultra or Snapdragon processor\n• 16GB memory, fast SSD\n• Premium aluminium build, under 1.2kg\n\nGenuine, brand new."],
                ['HP EliteBook 840 G11', 'Business laptop with Intel Core Ultra, security features and a great keyboard.',
                    "Built for work.\n\n• 14-inch display\n• Intel Core Ultra processor\n• 16GB memory, SSD storage\n• HP Wolf Security, fingerprint reader, backlit keyboard\n• Thunderbolt 4 and HDMI\n\nGenuine, brand new. Bulk pricing for teams."],
                ['HP ProBook 450 G10', 'Reliable 15.6" business laptop for offices and schools.',
                    "Dependable everyday work laptop.\n\n• 15.6-inch Full HD display\n• Intel Core i5 or i7 processor\n• 8GB to 16GB memory, SSD storage\n• Full keyboard with number pad\n\nGenuine, brand new. Bulk pricing for teams."],
                ['Lenovo ThinkPad X1 Carbon Gen 12', 'Ultra-light carbon-fibre business laptop with Intel Core Ultra.',
                    "The classic executive laptop.\n\n• 14-inch display\n• Intel Core Ultra processor\n• 16GB to 32GB memory\n• Carbon-fibre build, about 1.1kg\n• ThinkPad keyboard and TrackPoint\n\nGenuine, brand new."],
                ['Lenovo IdeaPad Slim 3', 'Affordable everyday laptop for school, home and office.',
                    "Great value for daily tasks.\n\n• 15.6-inch Full HD display\n• Intel Core i3/i5 or AMD Ryzen processor\n• 8GB memory, SSD storage\n\nGenuine, brand new."],
                ['ASUS Vivobook 15', 'Lightweight 15.6" laptop with a fast SSD at a budget price.',
                    "Simple, light and affordable.\n\n• 15.6-inch Full HD display\n• Intel Core or AMD Ryzen processor\n• 8GB to 16GB memory, SSD storage\n\nGenuine, brand new."],
                ['Acer Aspire 5', 'Solid all-rounder with a Full HD screen and plenty of ports.',
                    "Everyday performance without overspending.\n\n• 15.6-inch Full HD display\n• Intel Core i5 processor\n• 8GB to 16GB memory, SSD storage\n• USB-C, USB-A and HDMI\n\nGenuine, brand new."],
            ]],

            'Gadgets & Accessories' => ['physical', [
                ['AirPods Pro 2', 'Active Noise Cancellation, Adaptive Audio and a USB-C MagSafe case.',
                    "Apple's best earbuds.\n\n• Active Noise Cancellation and Transparency mode\n• Adaptive Audio and Personalised Spatial Audio\n• Up to 6 hours listening per charge, 30 hours with the case\n• USB-C MagSafe charging case with speaker\n\nGenuine, brand new."],
                ['AirPods 4', 'Open-fit AirPods with an improved fit, USB-C case and optional ANC.',
                    "Comfortable everyday AirPods.\n\n• Redesigned open-fit shape\n• Personalised Spatial Audio\n• USB-C charging case\n• Model with Active Noise Cancellation also available\n\nGenuine, brand new."],
                ['Apple Watch Series 10', 'Thinnest Apple Watch with the biggest display, health and fitness tracking.',
                    "Health and fitness on your wrist.\n\n• Large wide-angle OLED display\n• Heart rate, ECG, sleep tracking and workout tracking\n• Fast charging\n• Works with iPhone\n\nGenuine, brand new."],
                ['Samsung Galaxy Buds3 Pro', 'Noise-cancelling Samsung earbuds with Galaxy AI features.',
                    "Premium Samsung earbuds.\n\n• Adaptive Active Noise Cancellation\n• High-quality sound with 2-way speakers\n• Live translate with Galaxy AI on supported phones\n\nGenuine, brand new."],
                ['Sony WH-1000XM5', 'Industry-leading noise-cancelling over-ear headphones with 30-hour battery.',
                    "Perfect for travel and focus.\n\n• Best-in-class noise cancellation\n• Up to 30 hours of battery\n• Clear calls with multiple microphones\n• Lightweight, comfortable design\n\nGenuine, brand new."],
                ['JBL Flip 6', 'Rugged waterproof Bluetooth speaker with bold JBL sound.',
                    "Take your music anywhere.\n\n• Powerful JBL Original Pro Sound\n• IP67 waterproof and dustproof\n• Up to 12 hours of playtime\n\nGenuine, brand new."],
                ['Anker PowerCore 20000 Power Bank', 'High-capacity 20,000mAh power bank: charges a phone several times.',
                    "Never run out of battery.\n\n• 20,000mAh capacity\n• Fast charging for phones and tablets\n• USB-C and USB-A ports\n\nGenuine, brand new."],
                ['iPad (A16)', "Apple's everyday iPad with an 11\" Liquid Retina display.",
                    "For school, work and entertainment.\n\n• 11-inch Liquid Retina display\n• A16 chip\n• Works with Apple Pencil (USB-C) and Magic Keyboard Folio\n• USB-C, all-day battery\n\nGenuine, brand new."],
            ]],

            'Office & Networking' => ['physical', [
                ['Starlink Standard Kit', 'High-speed satellite internet anywhere in Nigeria. Includes dish, router and cables.',
                    "Fast, reliable internet even where fibre doesn't reach.\n\n• Standard dish, Wi-Fi router, cables and base\n• Easy self-install with the Starlink app\n• Ideal for offices, homes and remote sites\n\nMonthly Starlink service plan is paid separately. Installation support available: ask us."],
                ['TP-Link Deco X50 Mesh Wi-Fi (3-pack)', 'Whole-home Wi-Fi 6 mesh: strong signal in every room.',
                    "Say goodbye to dead zones.\n\n• Wi-Fi 6 mesh system, 3 units\n• Covers large homes and offices\n• Easy setup with the Deco app\n\nGenuine, brand new. Setup available."],
                ['HP LaserJet Pro M404dn', 'Fast, reliable black-and-white office laser printer with duplex printing.',
                    "Workhorse office printer.\n\n• Mono laser, fast print speed\n• Automatic two-sided printing\n• Ethernet networking\n\nGenuine, brand new."],
                ['Dell 24 Monitor P2425H', '24" Full HD IPS office monitor with adjustable stand.',
                    "A comfortable second screen.\n\n• 23.8-inch Full HD IPS display\n• Height, tilt and swivel adjustable stand\n• HDMI, DisplayPort and USB-C\n• ComfortView Plus to reduce blue light\n\nGenuine, brand new."],
                ['APC Back-UPS 1200VA', 'Battery backup that keeps PCs and routers running through power cuts.',
                    "Protect your work from NEPA.\n\n• 1200VA uninterruptible power supply\n• Battery backup and surge protection\n• Keeps a desktop, monitor and router running for short outages\n\nGenuine, brand new."],
            ]],

            'AI Subscriptions, Software & Services' => ['service', [
                ['ChatGPT Plus (1 month)', "OpenAI's popular plan: smarter models, more messages, image generation and voice.",
                    "Get more out of ChatGPT.\n\n• Access to OpenAI's advanced models with higher message limits\n• Image generation, file uploads and data analysis\n• Voice conversations and deep research tools\n• Create and use custom GPTs\n\nHow it works: tell us the email of the ChatGPT account to upgrade (or let us create one), pay once, and we activate Plus for one month and confirm with you.",
                    'Email of your ChatGPT account'],
                ['ChatGPT Pro (1 month)', "OpenAI's top plan for power users: the most capable models and highest limits.",
                    "For professionals who use ChatGPT all day.\n\n• Everything in Plus\n• OpenAI's most capable reasoning models\n• Much higher usage limits and priority access\n• Extended deep research\n\nHow it works: tell us the email of the ChatGPT account, pay once, and we activate Pro for one month.",
                    'Email of your ChatGPT account'],
                ['Google AI Pro (1 month)', 'Gemini Pro models, Deep Research, video generation and extra Google storage.',
                    "Google's AI plan (formerly Gemini Advanced).\n\n• Google's most capable Gemini models in the Gemini app\n• Deep Research and a very long context for big documents\n• AI video generation and NotebookLM with higher limits\n• Gemini in Gmail, Docs and other Google apps\n• Extra Google One cloud storage\n\nHow it works: tell us the Gmail address to activate, pay once, and we set it up for one month.",
                    'Gmail address to activate'],
                ['Claude Pro (1 month)', "Anthropic's Claude with higher limits, projects and the best writing and coding help.",
                    "A favourite for writing, research and code.\n\n• More usage than the free plan\n• Projects to organise chats and documents\n• Access to Anthropic's latest Claude models\n• Claude Code for developers\n\nHow it works: tell us the email of your Claude account, pay once, and we activate Pro for one month.",
                    'Email of your Claude account'],
                ['Perplexity Pro (1 month)', 'AI search engine with cited answers and access to top AI models.',
                    "Research faster with sources.\n\n• Unlimited Pro searches with citations\n• Choose between leading AI models\n• Upload files and images to analyse\n\nHow it works: tell us your Perplexity account email, pay once, and we activate Pro.",
                    'Email of your Perplexity account'],
                ['Midjourney Standard (1 month)', 'Create stunning AI images and art with Midjourney.',
                    "The leading AI image generator.\n\n• Generous monthly image generations\n• Relaxed mode for unlimited slower generations\n• Commercial usage rights\n\nHow it works: tell us your Midjourney (Discord or web) account, pay once, and we activate it.",
                    'Midjourney account email or Discord username'],
                ['Cursor Pro (1 month)', 'AI code editor for developers: write, edit and understand code faster.',
                    "Code with AI built into your editor.\n\n• AI autocomplete and chat in a VS Code-style editor\n• Agent mode for multi-file changes\n• Higher usage of premium models\n\nHow it works: tell us your Cursor account email, pay once, and we activate Pro.",
                    'Email of your Cursor account'],
                ['X Premium (1 month)', 'Blue checkmark, edit posts, longer posts and videos, fewer ads and Grok.',
                    "Upgrade your X (Twitter) account.\n\n• Verified blue checkmark (subject to X's review)\n• Edit posts, longer posts and longer video uploads\n• Reply boost and fewer ads in For You and Following\n• Access to Grok\n• Creator monetisation eligibility\n\nHow it works: tell us your X username or profile link, pay once, and we activate Premium for one month.",
                    'X username or profile link'],
                ['X Premium+ (1 month)', 'Everything in Premium with no ads, the largest reply boost and more Grok.',
                    "X's top tier.\n\n• Everything in Premium\n• No ads in For You and Following\n• Largest reply prioritisation\n• Higher Grok limits\n\nHow it works: tell us your X username or profile link, pay once, and we activate Premium+.",
                    'X username or profile link'],
                ['Canva Pro (1 year)', 'Premium templates, Brand Kit, background remover and Magic Studio AI tools.',
                    "Design like a pro.\n\n• Millions of premium templates, photos and elements\n• Brand Kit for your logos, colours and fonts\n• Background remover, Magic Resize and AI tools\n• 1TB cloud storage\n\nHow it works: tell us the email of your Canva account, pay once, and we activate Pro for 12 months.",
                    'Email of your Canva account'],
                ['Microsoft 365 Personal (1 year)', 'Word, Excel, PowerPoint, Outlook and 1TB OneDrive storage.',
                    "The full Office apps.\n\n• Word, Excel, PowerPoint, Outlook and OneNote\n• 1TB OneDrive cloud storage\n• Works on PC, Mac, tablet and phone\n\nHow it works: tell us the Microsoft account email, pay once, and we activate it for 12 months.",
                    'Email of your Microsoft account'],
                ['Spotify Premium (1 month)', 'Ad-free music, offline downloads and unlimited skips.',
                    "Music without interruptions.\n\n• No ads\n• Download songs and podcasts to listen offline\n• Play any song, unlimited skips\n\nHow it works: tell us your Spotify account email, pay once, and we activate Premium.",
                    'Email of your Spotify account'],
                ['YouTube Premium (1 month)', 'Ad-free YouTube, background play, downloads and YouTube Music Premium.',
                    "YouTube without ads.\n\n• No ads on YouTube videos\n• Play in the background and download videos\n• YouTube Music Premium included\n\nHow it works: tell us the Google account email, pay once, and we activate Premium.",
                    'Email of your Google / YouTube account'],
            ]],
        ];
    }
}
