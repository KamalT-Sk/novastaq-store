<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.products.index', [
            'products' => Product::with('category', 'images')
                ->when($q !== '', fn ($w) => $w->where('name', 'like', "%{$q}%"))
                ->when($request->query('category'), fn ($w, $c) => $w->where('category_id', $c))
                ->latest()->paginate(25)->withQueryString(),
            'categories' => Category::orderBy('sort')->get(),
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product(['is_active' => true, 'type' => 'physical']), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));
        $this->saveImages($request, $product);

        return redirect()->route('admin.products.edit', $product)->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product->load('images'), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        // A Paystack plan's price and interval are fixed, so a change means a fresh plan on next subscribe.
        if ($product->price !== $data['price'] || $product->billing_interval !== $data['billing_interval']) {
            $data['paystack_plan_code'] = null;
        }

        $product->update($data);

        foreach ($product->images()->whereIn('id', (array) $request->input('remove_images', []))->get() as $img) {
            Storage::disk('public')->delete($img->path);
            $img->delete();
        }
        $this->saveImages($request, $product);

        return back()->with('status', 'Product saved.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Products that were ever ordered stay for the records; they are just hidden.
        if ($product->hasOrders()) {
            $product->update(['is_active' => false]);

            return redirect()->route('admin.products.index')->with('status', "{$product->name} has orders, so it was hidden instead of deleted.");
        }

        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'alpha_dash', 'max:190', Rule::unique('products', 'slug')->ignore($product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', Rule::in(['physical', 'subscription'])],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'regex:/^[\d,]+(\.\d{1,2})?$/'],
            'compare_at_price' => ['nullable', 'regex:/^[\d,]+(\.\d{1,2})?$/'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'billing_interval' => ['nullable', 'required_if:type,subscription', Rule::in(array_keys(Product::INTERVALS))],
            'images.*' => ['image', 'max:5120'],
        ], ['price.regex' => 'Enter the price in naira, e.g. 450000 or 450,000.']);

        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name'], $product);
        $data['price'] = Money::toKobo($data['price']);
        $data['compare_at_price'] = filled($data['compare_at_price'] ?? null) ? Money::toKobo($data['compare_at_price']) : null;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        if ($data['type'] === 'subscription') {
            $data['stock'] = null;
        } else {
            $data['billing_interval'] = null;
        }
        unset($data['images']);

        return $data;
    }

    private function saveImages(Request $request, Product $product): void
    {
        $sort = (int) $product->images()->max('sort');
        foreach ((array) $request->file('images', []) as $file) {
            ProductImage::create(['product_id' => $product->id, 'path' => $file->store('products', 'public'), 'sort' => ++$sort]);
        }
    }

    private function uniqueSlug(string $name, ?Product $product): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        for ($i = 2; Product::where('slug', $slug)->whereKeyNot($product?->id)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
