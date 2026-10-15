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
            'services' => Product::active()->where('type', 'service')->with('images')->orderBy('price')->take(6)->get(),
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

    public function checkDiscount(Request $request): \Illuminate\Http\JsonResponse
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $productId = $request->input('product_id');
        $subtotalKobo = (int) $request->input('subtotal');

        if ($code === '') {
            return response()->json(['valid' => false, 'message' => 'Please enter a discount code.'], 422);
        }

        $discount = \App\Models\DiscountCode::where('code', $code)->first();
        if (! $discount || ! $discount->is_active) {
            return response()->json(['valid' => false, 'message' => 'Invalid discount code.'], 422);
        }

        if ($discount->expires_at && $discount->expires_at->isPast()) {
            return response()->json(['valid' => false, 'message' => 'This discount code has expired.'], 422);
        }

        if ($discount->max_uses !== null && $discount->uses_count >= $discount->max_uses) {
            return response()->json(['valid' => false, 'message' => 'This discount code has reached its usage limit.'], 422);
        }

        $product = $productId ? Product::find($productId) : null;
        $cartItems = (array) $request->input('cart_items', []);

        if ($discount->product_id !== null) {
            $matchingCartItem = collect($cartItems)->first(fn ($item) => isset($item['product_id']) && (int) $item['product_id'] === (int) $discount->product_id);

            if ($product) {
                if ((int) $discount->product_id !== (int) $product->id) {
                    $targetName = $discount->product?->name ?? 'another product';

                    return response()->json(['valid' => false, 'message' => "This code is only valid for {$targetName}."], 422);
                }
            } elseif ($matchingCartItem) {
                if (! empty($matchingCartItem['total'])) {
                    $subtotalKobo = (int) $matchingCartItem['total'];
                }
            } else {
                $targetName = $discount->product?->name ?? 'another product';

                return response()->json(['valid' => false, 'message' => "This code is only valid for {$targetName}."], 422);
            }
        }

        if ($subtotalKobo <= 0 && $product) {
            $subtotalKobo = $product->price;
        }

        $discountAmountKobo = $discount->calculateDiscount($subtotalKobo);
        $finalTotalKobo = max(0, $subtotalKobo - $discountAmountKobo);

        return response()->json([
            'valid' => true,
            'code' => $discount->code,
            'percent_off' => $discount->percent_off,
            'discount_amount' => $discountAmountKobo,
            'discount_formatted' => \App\Support\Money::naira($discountAmountKobo),
            'original_price' => $subtotalKobo,
            'original_price_formatted' => \App\Support\Money::naira($subtotalKobo),
            'final_price' => $finalTotalKobo,
            'final_price_formatted' => \App\Support\Money::naira($finalTotalKobo),
        ]);
    }
}

