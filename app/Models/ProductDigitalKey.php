<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDigitalKey extends Model
{
    protected $fillable = ['product_id', 'key_content', 'order_item_id', 'assigned_at'];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function isAssigned(): bool
    {
        return $this->order_item_id !== null;
    }

    public function getKeyContentAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim((string) preg_replace('/(?:[\s\-_]+(?:\d{1,3}[\.\)\:\-]|\[\d{1,3}\])|\[\d{1,3}\]|(?<=[^\d\s])\d{1,3}[\.\)\:]|\.)$/u', '', trim($value)));
    }

    public function setKeyContentAttribute(?string $value): void
    {
        $this->attributes['key_content'] = $value === null
            ? null
            : trim((string) preg_replace('/(?:[\s\-_]+(?:\d{1,3}[\.\)\:\-]|\[\d{1,3}\])|\[\d{1,3}\]|(?<=[^\d\s])\d{1,3}[\.\)\:]|\.)$/u', '', trim($value)));
    }
}

