<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generates a dynamic XML sitemap for search engines (Google, Bing).
     */
    public function __invoke(): Response
    {
        $categories = Category::orderBy('sort')->get();
        $products = Product::active()->with('images')->latest('updated_at')->get();

        $urls = [];

        // Static Pages
        $urls[] = [
            'loc' => route('home'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
            'image' => url('/logo.png'),
            'title' => 'Novastaq Store Home',
        ];

        $urls[] = [
            'loc' => route('shop'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
            'image' => null,
            'title' => 'All Products',
        ];

        $urls[] = [
            'loc' => route('orders.track'),
            'lastmod' => now()->startOfMonth()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.6',
            'image' => null,
            'title' => 'Track Your Order',
        ];

        // Categories
        foreach ($categories as $cat) {
            $urls[] = [
                'loc' => route('category', $cat),
                'lastmod' => $cat->updated_at?->toAtomString() ?: now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'image' => null,
                'title' => $cat->name,
            ];
        }

        // Active Products
        foreach ($products as $prod) {
            $img = $prod->images->first()?->url();
            $urls[] = [
                'loc' => route('product', $prod),
                'lastmod' => $prod->updated_at?->toAtomString() ?: now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.85',
                'image' => $img,
                'title' => $prod->name,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($urls as $u) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8').'</loc>';
            $xml .= '<lastmod>'.$u['lastmod'].'</lastmod>';
            $xml .= '<changefreq>'.$u['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$u['priority'].'</priority>';

            if (! empty($u['image'])) {
                $xml .= '<image:image>';
                $xml .= '<image:loc>'.htmlspecialchars($u['image'], ENT_XML1, 'UTF-8').'</image:loc>';
                if (! empty($u['title'])) {
                    $xml .= '<image:title>'.htmlspecialchars($u['title'], ENT_XML1, 'UTF-8').'</image:title>';
                }
                $xml .= '</image:image>';
            }

            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

