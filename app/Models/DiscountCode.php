<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'percent_off',
        'product_id',
        'max_uses',
        'uses_count',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'percent_off' => 'integer',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if this discount code is currently valid for a given product or general order.
     */
    public function isValidForProduct(?Product $product = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        if ($this->product_id !== null && $product !== null && $this->product_id !== $product->id) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount in kobo from a given subtotal in kobo.
     */
    public function calculateDiscount(int $subtotalKobo): int
    {
        if ($subtotalKobo <= 0) {
            return 0;
        }

        $discount = (int) round($subtotalKobo * ($this->percent_off / 100));

        return min($discount, $subtotalKobo);
    }

    /**
     * Record a use of this discount code.
     */
    public function recordUse(): void
    {
        $this->increment('uses_count');
    }

    public function formattedReduction(): string
    {
        return "{$this->percent_off}% off";
    }
}

