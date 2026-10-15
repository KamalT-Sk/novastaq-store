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
                ->when($request->query('status') === 'live', fn ($w) => $w->where('is_active', true))
                ->when($request->query('status') === 'hidden', fn ($w) => $w->where('is_active', false))
                ->when($request->query('status') === 'needs-price', fn ($w) => $w->where('price', 0))
                ->orderBy('category_id')->orderBy('name')->paginate(50)->withQueryString(),
            'categories' => Category::orderBy('sort')->get(),
            'q' => $q,
            'counts' => [
                'live' => Product::where('is_active', true)->count(),
                'hidden' => Product::where('is_active', false)->count(),
                'needs-price' => Product::where('price', 0)->count(),
            ],
        ]);
    }

    /** One-click Live / Hidden from the products list. A product needs a price to go live. */
    public function toggle(Product $product): RedirectResponse
    {
        if (! $product->is_active && $product->price <= 0) {
            return back()->with('error', "Add a price to {$product->name} before making it live.");
        }

        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->is_active ? "{$product->name} is now live." : "{$product->name} is hidden from the store.");
    }

    public function create(Request $request): View
    {
        // After "Create product" the form comes back blank, keeping the category and type for the next one.
        $product = new Product([
            'is_active' => $request->user()->hasAccess('publish'),
            'type' => in_array($request->query('type'), ['service', 'digital'], true) ? $request->query('type') : 'physical',
            'category_id' => $request->query('category'),
            'digital_type' => 'pool',
        ]);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
            'justAdded' => $request->query('added') ? Product::find($request->query('added')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));
        $this->saveImages($request, $product);
        $this->saveDigitalKeys($request, $product);

        return redirect()->route('admin.products.create', ['category' => $product->category_id, 'type' => $product->type, 'added' => $product->id]);
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product->load(['images', 'digitalKeys', 'discountCodes']),
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        if ($request->boolean('remove_digital_file') && $product->digital_file) {
            Storage::disk('public')->delete($product->digital_file);
            $data['digital_file'] = null;
        }

        $product->update($data);

        foreach ($product->images()->whereIn('id', (array) $request->input('remove_images', []))->get() as $img) {
            Storage::disk('public')->delete($img->path);
            $img->delete();
        }
        $this->saveImages($request, $product);
        $this->saveDigitalKeys($request, $product);

        return redirect()->route('admin.products.index')->with('status', "{$product->name} saved.");
    }

    public function destroyKey(Product $product, \App\Models\ProductDigitalKey $key): RedirectResponse
    {
        abort_unless($key->product_id === $product->id, 404);
        if ($key->isAssigned()) {
            return back()->with('error', 'Cannot remove a key that has already been delivered to a customer.');
        }

        $key->delete();

        return back()->with('status', 'Digital link removed from pool.');
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
        if ($product->digital_file) {
            Storage::disk('public')->delete($product->digital_file);
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
            'type' => ['required', Rule::in(['physical', 'service', 'digital'])],
            'digital_type' => ['nullable', Rule::in(['pool', 'file'])],
            'digital_url' => ['nullable', 'string', 'max:1000'],
            'digital_instructions' => ['nullable', 'string', 'max:5000'],
            'digital_file' => ['nullable', 'file', 'max:25600'],
            'add_digital_keys' => ['nullable', 'string', 'max:50000'],
            'account_label' => ['nullable', 'string', 'max:120'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'regex:/^[\d,]+(\.\d{1,2})?$/'],
            'compare_at_price' => ['nullable', 'regex:/^[\d,]+(\.\d{1,2})?$/'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'images.*' => ['image', 'max:5120'],
        ], ['price.regex' => 'Enter the price in naira, e.g. 450000 or 450,000.']);

        $data['slug'] = ($data['slug'] ?? null) ?: $this->uniqueSlug($data['name'], $product);
        $data['price'] = Money::toKobo($data['price']);
        $data['compare_at_price'] = filled($data['compare_at_price'] ?? null) ? Money::toKobo($data['compare_at_price']) : null;
        // Staff without "publish" (catalogue editors) can't change Live/Hidden: new products start hidden.
        $data['is_active'] = $request->user()->hasAccess('publish') ? $request->boolean('is_active') : (bool) $product?->is_active;
        $data['is_featured'] = $request->boolean('is_featured');
        if ($data['is_active'] && $data['price'] <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['price' => 'Set a price before making this product live (or untick "Live in the store").']);
        }

        if ($request->hasFile('digital_file')) {
            if ($product?->digital_file) {
                Storage::disk('public')->delete($product->digital_file);
            }
            $data['digital_file'] = $request->file('digital_file')->store('digital', 'public');
        }

        if ($data['type'] === 'service') {
            $data['stock'] = null; // services aren't counted
            $data['compare_at_price'] = null;
            $data['digital_type'] = 'pool';
            $data['digital_url'] = null;
            $data['digital_file'] = null;
            $data['digital_instructions'] = null;
        } elseif ($data['type'] === 'digital') {
            $data['account_label'] = null;
            $data['compare_at_price'] = null;
            $data['stock'] = null;
            $data['digital_type'] = $data['digital_type'] ?: 'pool';
        } else {
            $data['account_label'] = null;
            $data['digital_type'] = 'pool';
            $data['digital_url'] = null;
            $data['digital_file'] = null;
            $data['digital_instructions'] = null;
        }

        unset($data['images'], $data['add_digital_keys']);

        return $data;
    }

    private function saveDigitalKeys(Request $request, Product $product): void
    {
        if (! $request->filled('add_digital_keys')) {
            return;
        }

        $keys = $this->parseDigitalKeys((string) $request->input('add_digital_keys'));
        foreach ($keys as $key) {
            $product->digitalKeys()->create(['key_content' => $key]);
        }
    }

    /**
     * Parses digital keys / links from textarea input.
     * If the input contains URLs (https?://), it joins wrapped lines belonging to the same URL
     * so that long URLs split across 3-4 lines are automatically reconstructed into a single link.
     *
     * @return list<string>
     */
    public function parseDigitalKeys(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        if (preg_match('#https?://#i', $raw)) {
            // Automatically split URLs glued together without a newline (e.g. "...==https://..." or "... https://...")
            $normalized = preg_replace('/([^\r\n\s])(https?:\/\/)/i', "$1\n$2", $raw);
            $normalized = preg_replace('/[ \t]+(https?:\/\/)/i', "\n$1", (string) $normalized);

            $lines = preg_split('/[\r\n]+/', (string) $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $keys = [];
            $current = '';

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                if (preg_match('#^https?://#i', $trimmed)) {
                    if ($current !== '') {
                        $keys[] = $current;
                    }
                    $current = $trimmed;
                } elseif ($current !== '') {
                    // Continuation of previous URL (wrapped without http prefix)
                    $current .= $trimmed;
                }
            }

            if ($current !== '') {
                $keys[] = $current;
            }

            return $keys;
        }

        $lines = preg_split('/[\r\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $lines)));
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
