<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'name', 'account_details',
        'delivery_type', 'digital_content', 'digital_file', 'digital_instructions',
        'unit_price', 'quantity', 'line_total',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function digitalKey()
    {
        return $this->hasOne(ProductDigitalKey::class);
    }

    public function isDigital(): bool
    {
        return $this->delivery_type === 'digital'
            || ($this->product && $this->product->isDigital())
            || ! empty($this->digital_content)
            || ! empty($this->digital_file);
    }

    public function hasDigitalDelivery(): bool
    {
        return ! empty($this->digital_content) || ! empty($this->digital_file);
    }
}
