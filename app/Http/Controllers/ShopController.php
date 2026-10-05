<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function home(): View
    {
        return view('shop.home', [
            'categories' => Category::withCount(['products' => fn ($q) => $q->active()])->orderBy('sort')->get(),
            'featured' => Product::active()->where('is_featured', true)->with('images')->latest()->take(8)->get(),
            'latest' => Product::active()->where('type', 'physical')->with('images')->latest()->take(8)->get(),
            'plans' => Product::active()->where('type', 'subscription')->with('images')->orderBy('price')->take(6)->get(),
        ]);
    }

    public function index(Request $request, ?Category $category = null): View
    {
        $q = trim((string) $request->query('q'));

        $products = Product::active()->with('images')
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('short_description', 'like', "%{$q}%")))
            ->when($request->query('sort') === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($request->query('sort') === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->latest()
            ->paginate(24)->withQueryString();

        return view('shop.index', [
            'category' => $category,
            'categories' => Category::orderBy('sort')->get(),
            'products' => $products,
            'q' => $q,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load('images', 'category');

        return view('shop.show', [
            'product' => $product,
            'related' => Product::active()->with('images')->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)->inRandomOrder()->take(4)->get(),
        ]);
    }
}
