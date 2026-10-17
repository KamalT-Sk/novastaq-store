<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDigitalKey;
use App\Models\ProductImage;
use App\Support\DigitalKeyImport;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $with = ['category', 'images'];
        try {
            if (Schema::hasTable('product_variants')) {
                $with[] = 'variants';
            }
        } catch (\Throwable) {
        }

        return view('admin.products.index', [
            'products' => Product::with($with)
                ->when($q !== '', fn ($w) => $w->where('name', 'like', "%{$q}%"))
                ->when($request->query('category'), fn ($w, $c) => $w->where('category_id', $c))
                ->when($request->query('status') === 'live', fn ($w) => $w->where('is_active', true))
                ->when($request->query('status') === 'hidden', fn ($w) => $w->where('is_active', false))
                ->when($request->query('status') === 'needs-price', fn ($w) => $w->where('price', 0)->where('ask_for_price', false))
                ->orderBy('category_id')->orderBy('name')->paginate(50)->withQueryString(),
            'categories' => Category::orderBy('sort')->get(),
            'q' => $q,
            'counts' => [
                'live' => Product::where('is_active', true)->count(),
                'hidden' => Product::where('is_active', false)->count(),
                'needs-price' => Product::where('price', 0)->where('ask_for_price', false)->count(),
            ],
        ]);
    }

    /** One-click Live / Hidden from the products list. A product needs a price to go live unless on-demand. */
    public function toggle(Product $product): RedirectResponse
    {
        if (! $product->is_active && $product->price <= 0 && ! $product->isPriceOnDemand()) {
            return back()->with('error', "Add a price to {$product->name} before making it live (or enable Ask for price).");
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
        $data = $this->validated($request);
        $product = $this->persistProduct($request, $data);

        return redirect()->route('admin.products.create', ['category' => $product->category_id, 'type' => $product->type, 'added' => $product->id]);
    }

    public function edit(Request $request, Product $product): View
    {
        $with = ['images', 'discountCodes'];
        try {
            if (Schema::hasTable('product_variants')) {
                $with[] = 'variants';
            }
        } catch (\Throwable) {
        }

        $product->load($with);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function storeKeys(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $this->validateKeyImport($request);
        $created = $this->importKeys($request, $product);

        $count = count($created);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'available_count' => $product->availableDigitalKeysCount(),
                'total_count' => $product->digitalKeys()->count(),
                'message' => $count.' '.Str::plural('link', $count).' added to pool successfully.',
                'keys' => collect($created)->map(fn ($k) => [
                    'id' => $k->id,
                    'key_content' => $k->key_content,
                    'destroy_url' => route('admin.digital-keys.destroy', $k),
                ]),
            ]);
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('status', $count.' '.Str::plural('link', $count).' added to pool successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $product = $this->persistProduct($request, $data, $product);

        return redirect()->route($request->input('save_action') === 'continue' ? 'admin.products.edit' : 'admin.products.index',
            $request->input('save_action') === 'continue' ? [$product] : [])
            ->with('status', "{$product->name} saved.");
    }

    /** Database rollback and compensating cleanup for newly uploaded files. */
    private function persistProduct(Request $request, array $data, ?Product $product = null): Product
    {
        $uploaded = [];
        $obsoleteImages = [];
        try {
            $saved = DB::transaction(function () use ($request, $data, $product, &$uploaded, &$obsoleteImages) {
                if ($product) {
                    $product = Product::lockForUpdate()->findOrFail($product->id);
                }
                if ($request->hasFile('digital_file') && $data['type'] === 'digital') {
                    $path = $request->file('digital_file')->store('digital', 'local');
                    if (! $path) {
                        throw new \RuntimeException('Unable to save the download file.');
                    }
                    $uploaded[] = ['local', $path];
                    $data['digital_file'] = $path;
                } elseif ($request->boolean('remove_digital_file')) {
                    $data['digital_file'] = null;
                }
                // Old downloads remain available to orders that reference them.
                if ($product) {
                    $product->update($data);
                } else {
                    $product = Product::create($data);
                }
                foreach ($product->images()->whereIn('id', (array) $request->input('remove_images', []))->get() as $image) {
                    $obsoleteImages[] = $image->path;
                    $image->delete();
                }
                $this->saveVariants($request, $product);
                $this->saveImages($request, $product, $uploaded);
                $this->saveDigitalKeys($request, $product);

                return $product;
            });
        } catch (\Throwable $e) {
            foreach ($uploaded as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        }
        foreach ($obsoleteImages as $path) {
            Storage::disk('public')->delete($path);
        }

        return $saved;
    }

    public function destroyKeyDirect(Request $request, string $key): RedirectResponse|JsonResponse
    {
        return $this->removeKey($request, $key);
    }

    public function destroyKey(Request $request, Product $product, string $key): RedirectResponse|JsonResponse
    {
        return $this->removeKey($request, $key, $product);
    }

    private function removeKey(Request $request, string $id, ?Product $product = null): RedirectResponse|JsonResponse
    {
        $result = DB::transaction(function () use ($id, &$product) {
            $key = ProductDigitalKey::whereKey($id)->lockForUpdate()->first();
            if (! $key) {
                return 'already_removed';
            }
            abort_if($product && (int) $key->product_id !== (int) $product->id, 404);
            $product ??= $key->product;
            if ($key->isAssigned()) {
                return 'assigned';
            }
            $deleted = ProductDigitalKey::whereKey($id)->whereNull('order_item_id')->whereNull('assigned_at')->delete();

            return $deleted ? 'removed' : 'assigned';
        });
        if ($result === 'assigned') {
            $message = 'Cannot remove a key that has already been delivered to a customer.';

            return $request->wantsJson() ? response()->json(['error' => $message], 422) : back()->with('error', $message);
        }
        $message = $result === 'already_removed' ? 'Digital link already removed.' : 'Digital link removed from pool.';
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true, 'already_removed' => $result === 'already_removed',
                'available_count' => $product?->availableDigitalKeysCount() ?? 0,
                'total_count' => $product?->digitalKeys()->count() ?? 0, 'message' => $message,
            ]);
        }

        return ($product ? redirect()->route('admin.products.edit', $product) : back())->with('status', $message);
    }

    public function keys(Request $request, Product $product): JsonResponse
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 190);
        $keys = $product->digitalKeys()
            ->when($search !== '', fn ($q) => $q->where('key_content', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(25);
        $keys->through(fn ($key) => ['id' => $key->id, 'key_content' => $key->key_content,
            'assigned' => $key->isAssigned(), 'destroy_url' => route('admin.products.keys.destroy', [$product, $key])]);

        return response()->json(['keys' => $keys, 'available' => $product->availableDigitalKeysCount(),
            'delivered' => $product->digitalKeys()->where(fn ($q) => $q->whereNotNull('order_item_id')->orWhereNotNull('assigned_at'))->count(),
            'total' => $product->digitalKeys()->count()]);
    }

    public function previewKeys(Request $request, ?Product $product = null): JsonResponse
    {
        $this->validateKeyImport($request);

        return response()->json(DigitalKeyImport::preview($product, (string) $request->input('add_digital_keys'), $request->input('key_import_mode', 'lines')));
    }

    private function validateKeyImport(Request $request): void
    {
        $request->validate([
            'add_digital_keys' => ['required', 'string', 'max:50000'],
            'key_import_mode' => ['nullable', Rule::in(['lines', 'list', 'wrapped'])],
        ]);
    }

    private function importKeys(Request $request, Product $product): array
    {
        return DB::transaction(function () use ($request, $product) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $preview = DigitalKeyImport::preview($product, (string) $request->input('add_digital_keys'), $request->input('key_import_mode', 'lines'));
            if ($preview['rejected'] !== []) {
                throw ValidationException::withMessages(['add_digital_keys' => 'Some entries contain control characters or exceed 5,000 characters. Review the preview.']);
            }
            $created = [];
            foreach ($preview['keys'] as $key) {
                $created[] = $product->digitalKeys()->create(['key_content' => $key]);
            }

            return $created;
        });
    }

    public function uploadImages(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $uploaded = [];
        try {
            DB::transaction(function () use ($request, $product, &$uploaded) {
                $this->saveImages($request, $product, $uploaded);
            });
        } catch (\Throwable $e) {
            foreach ($uploaded as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        }

        $count = count((array) $request->file('images', []));

        return back()->with('status', $count.' '.Str::plural('image', $count).' uploaded successfully.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('status', 'Image deleted.');
    }

    public function destroyImagesBatch(Request $request, Product $product): RedirectResponse
    {
        $ids = (array) $request->input('remove_images', []);
        if (empty($ids)) {
            return back()->with('error', 'No images selected for deletion.');
        }

        $count = 0;
        foreach ($product->images()->whereIn('id', $ids)->get() as $img) {
            Storage::disk('public')->delete($img->path);
            $img->delete();
            $count++;
        }

        return back()->with('status', $count.' '.Str::plural('image', $count).' deleted.');
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
            Storage::disk('local')->delete($product->digital_file);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Product $product = null): array
    {
        $isAskForPrice = $request->boolean('ask_for_price');
        $hasVariants = ! $isAskForPrice && $request->boolean('has_variants') && ! empty($request->input('variants'));
        if ($hasVariants) {
            $firstVariantPrice = collect($request->input('variants'))->pluck('price')->filter()->first();
            if (empty($request->input('price')) && $firstVariantPrice) {
                $request->merge(['price' => $firstVariantPrice]);
            }
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'alpha_dash', 'max:190', Rule::unique('products', 'slug')->ignore($product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', Rule::in(['physical', 'service', 'digital'])],
            'digital_type' => ['nullable', Rule::in(['pool', 'file'])],
            'digital_url' => ['nullable', 'url:http,https', 'max:1000'],
            'digital_instructions' => ['nullable', 'string', 'max:5000'],
            'digital_file' => ['nullable', 'file', 'mimes:pdf,zip', 'max:25600'],
            'add_digital_keys' => ['nullable', 'string', 'max:50000'],
            'key_import_mode' => ['nullable', Rule::in(['lines', 'list', 'wrapped'])],
            'remove_digital_file' => ['nullable', 'boolean'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'remove_digital_keys' => ['nullable', 'array'],
            'remove_digital_keys.*' => ['integer'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'account_label' => ['nullable', 'string', 'max:120'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => [($hasVariants || $isAskForPrice) ? 'nullable' : 'required', 'regex:/^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/'],
            'compare_at_price' => ['nullable', 'regex:/^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/'],
            'ask_for_price' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'has_variants' => ['nullable', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product?->id ?? 0)],
            'variants.*.name' => ['required_with:variants', 'string', 'max:190'],
            'variants.*.price' => ['required_with:variants', 'regex:/^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/'],
            'variants.*.compare_at_price' => ['nullable', 'regex:/^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], ['price.regex' => 'Enter the price in naira, e.g. 450000 or 450,000.']);

        $data['slug'] = ($data['slug'] ?? null) ?: ($product?->slug ?: $this->uniqueSlug($data['name'], $product));
        $data['ask_for_price'] = $isAskForPrice;

        if ($isAskForPrice) {
            $data['price'] = filled($data['price'] ?? null) ? Money::toKobo($data['price']) : 0;
        } elseif ($hasVariants) {
            $minVariantPrice = collect($request->input('variants'))
                ->map(fn ($v) => filled($v['price'] ?? null) ? Money::toKobo($v['price']) : null)
                ->filter()
                ->min();
            $data['price'] = $minVariantPrice ?: ($data['price'] ? Money::toKobo($data['price']) : 0);
        } else {
            $data['price'] = Money::toKobo($data['price']);
        }

        $data['compare_at_price'] = filled($data['compare_at_price'] ?? null) ? Money::toKobo($data['compare_at_price']) : null;
        // Staff without "publish" (catalogue editors) can't change Live/Hidden: new products start hidden.
        $data['is_active'] = $request->user()->hasAccess('publish') ? $request->boolean('is_active') : (bool) $product?->is_active;
        $data['is_featured'] = $request->boolean('is_featured');
        if ($data['is_active'] && $data['price'] <= 0 && ! $data['ask_for_price']) {
            throw ValidationException::withMessages(['price' => 'Set a price before making this product live (or enable "Ask for price").']);
        }

        if ($data['type'] === 'service') {
            $data['stock'] = isset($data['stock']) && $data['stock'] !== '' ? (int) $data['stock'] : null;
            $data['compare_at_price'] = null;
            $data['digital_type'] = 'pool';
            $data['digital_url'] = null;
            $data['digital_file'] = null;
            $data['digital_instructions'] = null;
        } elseif ($data['type'] === 'digital') {
            $data['account_label'] = null;
            $data['compare_at_price'] = null;
            $data['stock'] = null;
            $data['digital_type'] = ($data['digital_type'] ?? null) ?: 'pool';
        } else {
            $data['account_label'] = null;
            $data['digital_type'] = 'pool';
            $data['digital_url'] = null;
            $data['digital_file'] = null;
            $data['digital_instructions'] = null;
        }

        unset($data['images'], $data['add_digital_keys'], $data['has_variants'], $data['variants'], $data['digital_file'], $data['remove_digital_file'], $data['remove_images'], $data['remove_digital_keys'], $data['key_import_mode']);
        if ($data['type'] !== 'digital') {
            $data['digital_file'] = null;
        }

        return $data;
    }

    private function saveVariants(Request $request, Product $product): void
    {
        $hasVariants = $request->boolean('has_variants');
        $rawVariants = (array) $request->input('variants', []);

        if (! $hasVariants || empty($rawVariants)) {
            $product->variants()->delete();

            return;
        }

        $keptIds = [];
        $sort = 0;
        foreach ($rawVariants as $item) {
            $name = trim($item['name'] ?? '');
            $priceRaw = $item['price'] ?? null;
            if ($name === '' || empty($priceRaw)) {
                continue;
            }

            $priceKobo = Money::toKobo($priceRaw);
            $compareAtKobo = filled($item['compare_at_price'] ?? null) ? Money::toKobo($item['compare_at_price']) : null;
            $stock = isset($item['stock']) && $item['stock'] !== '' ? (int) $item['stock'] : null;
            $isActive = isset($item['is_active']) ? (bool) $item['is_active'] : true;

            $variantData = [
                'name' => $name,
                'price' => $priceKobo,
                'compare_at_price' => $compareAtKobo,
                'stock' => $stock,
                'is_active' => $isActive,
                'sort' => $sort++,
            ];

            if (! empty($item['id'])) {
                $variant = $product->variants()->find($item['id']);
                if ($variant) {
                    $variant->update($variantData);
                    $keptIds[] = $variant->id;

                    continue;
                }
            }

            $newVariant = $product->variants()->create($variantData);
            $keptIds[] = $newVariant->id;
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();

        $minPrice = $product->variants()->where('is_active', true)->min('price');
        if ($minPrice !== null && $minPrice > 0) {
            $product->updateQuietly(['price' => $minPrice]);
        }
    }

    private function saveDigitalKeys(Request $request, Product $product): void
    {
        if ($request->has('remove_digital_keys')) {
            $removeIds = (array) $request->input('remove_digital_keys', []);
            if (! empty($removeIds)) {
                $product->digitalKeys()->whereIn('id', $removeIds)->whereNull('order_item_id')->whereNull('assigned_at')->delete();
            }
        }

        if (! $request->filled('add_digital_keys')) {
            return;
        }

        $this->importKeys($request, $product);
    }

    public function parseDigitalKeys(string $raw, string $mode = 'lines'): array
    {
        return DigitalKeyImport::parse($raw, $mode);
    }

    private function saveImages(Request $request, Product $product, array &$uploaded = []): void
    {
        $sort = (int) $product->images()->max('sort');
        foreach ((array) $request->file('images', []) as $file) {
            $path = $file->store('products', 'public');
            if (! $path) {
                throw new \RuntimeException('Unable to save the image.');
            }
            $uploaded[] = ['public', $path];
            ProductImage::create(['product_id' => $product->id, 'path' => $path, 'sort' => ++$sort]);
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
