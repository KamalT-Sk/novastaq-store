<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Session cart: product id => quantity. Prices are always read fresh from the
 * database, so a stale session can never set the price.
 */
class Cart
{
    private const KEY = 'cart';

    /** @return array<int, int> */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(Product $product, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$product->id] = min(($cart[$product->id] ?? 0) + $qty, 99);
        session([self::KEY => $cart]);
    }

    public function set(int $productId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty < 1) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min($qty, 99);
        }
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<int, object{product: Product, quantity: int, total: int}> */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }

        return Product::active()->whereIn('type', ['physical', 'digital'])->with('images')->whereIn('id', array_keys($raw))->get()
            ->map(fn (Product $p) => (object) ['product' => $p, 'quantity' => $raw[$p->id], 'total' => $p->price * $raw[$p->id]]);
    }

    public function subtotal(): int
    {
        return $this->lines()->sum('total');
    }
}
