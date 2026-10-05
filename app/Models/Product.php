<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'type', 'short_description', 'description', 'price',
        'compare_at_price', 'stock', 'billing_interval', 'paystack_plan_code', 'is_active', 'is_featured',
    ];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean'];

    public const INTERVALS = ['monthly' => 'month', 'quarterly' => 'quarter', 'annually' => 'year'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function isSubscription(): bool
    {
        return $this->type === 'subscription';
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

    /** Ever ordered or subscribed to, so it must be kept for the records. */
    public function hasOrders(): bool
    {
        return OrderItem::where('product_id', $this->id)->exists() || Subscription::where('product_id', $this->id)->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
