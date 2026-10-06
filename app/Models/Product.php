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
        'category_id', 'name', 'slug', 'type', 'account_label', 'short_description', 'description', 'price',
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

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function isService(): bool
    {
        return $this->type === 'service';
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
