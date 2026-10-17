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
        return $this->order_item_id !== null || $this->assigned_at !== null;
    }
}
