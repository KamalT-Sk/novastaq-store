<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * type "physical": a device, delivered.
 * type "service": a plan we activate for the customer (e.g. "X Premium, 1 month"),
 *                 bought once; the customer says which account it is for.
 */
class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'type', 'account_label',
        'digital_type', 'digital_url', 'digital_file', 'digital_instructions',
        'short_description', 'description', 'price',
        'compare_at_price', 'stock', 'is_active', 'is_featured',
    ];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function digitalKeys(): HasMany
    {
        return $this->hasMany(ProductDigitalKey::class);
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function hasVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->isNotEmpty();
        }

        return $this->activeVariants()->exists();
    }

    public function minPrice(): int
    {
        if ($this->hasVariants()) {
            $variants = $this->relationLoaded('variants') ? $this->variants->where('is_active', true) : $this->activeVariants()->get();
            $min = $variants->min('price');

            return $min !== null ? (int) $min : (int) $this->price;
        }

        return (int) $this->price;
    }

    public function maxPrice(): int
    {
        if ($this->hasVariants()) {
            $variants = $this->relationLoaded('variants') ? $this->variants->where('is_active', true) : $this->activeVariants()->get();
            $max = $variants->max('price');

            return $max !== null ? (int) $max : (int) $this->price;
        }

        return (int) $this->price;
    }

    public function priceDisplay(): string
    {
        if ($this->hasVariants()) {
            $min = $this->minPrice();
            $max = $this->maxPrice();
            if ($min !== $max) {
                return 'From ' . \App\Support\Money::naira($min);
            }

            return \App\Support\Money::naira($min);
        }

        return \App\Support\Money::naira($this->price);
    }

    public function availableDigitalKeys(): HasMany
    {
        return $this->digitalKeys()->whereNull('order_item_id');
    }

    public function availableDigitalKeysCount(): int
    {
        return $this->availableDigitalKeys()->count();
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }

    /** What the customer is asked for when buying a service. */
    public function accountPrompt(): string
    {
        return $this->account_label ?: 'Account email, username or link to activate';
    }

    /** null stock means "not tracked", which is always available */
    public function inStock(int $qty = 1): bool
    {
        return $this->stock === null || $this->stock >= $qty;
    }

    public function coverUrl(): ?string
    {
        $img = $this->images->first();

        return $img ? asset('storage/'.$img->path) : null;
    }

    /** Ever ordered, so it must be kept for the records. */
    public function hasOrders(): bool
    {
        return OrderItem::where('product_id', $this->id)->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
