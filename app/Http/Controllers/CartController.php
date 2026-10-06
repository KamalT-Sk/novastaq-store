<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        return view('shop.cart', ['lines' => $cart->lines(), 'subtotal' => $cart->subtotal()]);
    }

    public function add(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        abort_unless($product->is_active && ! $product->isService(), 404);
        $qty = max(1, (int) $request->input('quantity', 1));

        if (! $product->inStock(($cart->raw()[$product->id] ?? 0) + $qty)) {
            return back()->with('error', "Sorry, only {$product->stock} of {$product->name} left in stock.");
        }

        $cart->add($product, $qty);

        return back()->with('status', "{$product->name} added to your cart.");
    }

    public function update(Request $request, Cart $cart): RedirectResponse
    {
        foreach ((array) $request->input('quantities', []) as $id => $qty) {
            $cart->set((int) $id, (int) $qty);
        }

        return redirect()->route('cart')->with('status', 'Cart updated.');
    }
}
