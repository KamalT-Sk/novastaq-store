<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountCodeController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $productId = $request->query('product_id');
        $q = trim((string) $request->query('q'));

        $discounts = DiscountCode::with('product')
            ->when($q !== '', fn ($w) => $w->where('code', 'like', "%{$q}%"))
            ->when($status === 'active', fn ($w) => $w->where('is_active', true))
            ->when($status === 'inactive', fn ($w) => $w->where('is_active', false))
            ->when($productId, fn ($w) => $w->where('product_id', $productId))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.discounts.index', [
            'discounts' => $discounts,
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'price', 'type']),
            'q' => $q,
            'status' => $status,
            'productId' => $productId,
            'stats' => [
                'total' => DiscountCode::count(),
                'active' => DiscountCode::where('is_active', true)->count(),
                'total_uses' => (int) DiscountCode::sum('uses_count'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $request->merge(['code' => $code]);

        $data = $request->validate([
            'code' => ['required', 'string', 'alpha_num', 'min:3', 'max:30', Rule::unique('discount_codes', 'code')],
            'percent_off' => ['required', 'integer', 'min:1', 'max:100'],
            'product_id' => ['nullable', 'exists:products,id'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['boolean'],
        ], [
            'code.unique' => 'A discount code with this name already exists.',
            'percent_off.min' => 'Discount must be at least 1%.',
            'percent_off.max' => 'Discount cannot exceed 100%.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $discount = DiscountCode::create($data);

        $productName = $discount->product ? $discount->product->name : 'all products';

        return back()->with('status', "Discount code {$discount->code} ({$discount->percent_off}% off for {$productName}) created.");
    }

    /** Quick creation from a product edit page */
    public function storeForProduct(Request $request, Product $product): RedirectResponse
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $request->merge(['code' => $code]);

        $data = $request->validate([
            'code' => ['required', 'string', 'alpha_num', 'min:3', 'max:30', Rule::unique('discount_codes', 'code')],
            'percent_off' => ['required', 'integer', 'min:1', 'max:100'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'code.unique' => 'A discount code with this name already exists.',
            'percent_off.min' => 'Discount must be at least 1%.',
            'percent_off.max' => 'Discount cannot exceed 100%.',
        ]);

        $data['product_id'] = $product->id;
        $data['is_active'] = true;

        $discount = DiscountCode::create($data);

        return back()->with('status', "Discount code {$discount->code} ({$discount->percent_off}% off) added for {$product->name}.");
    }

    public function toggle(DiscountCode $discount): RedirectResponse
    {
        $discount->update(['is_active' => ! $discount->is_active]);

        return back()->with('status', $discount->is_active ? "Code {$discount->code} activated." : "Code {$discount->code} deactivated.");
    }

    public function destroy(DiscountCode $discount): RedirectResponse
    {
        $code = $discount->code;
        $discount->delete();

        return back()->with('status', "Discount code {$code} deleted.");
    }
}

