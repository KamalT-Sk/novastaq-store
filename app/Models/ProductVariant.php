<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'price', 'compare_at_price',
        'stock', 'sort', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inStock(int $qty = 1): bool
    {
        return $this->stock === null || $this->stock >= $qty;
    }

    public function formattedPrice(): string
    {
        return Money::naira($this->price);
    }

    public function formattedCompareAtPrice(): ?string
    {
        return $this->compare_at_price ? Money::naira($this->compare_at_price) : null;
    }
}

