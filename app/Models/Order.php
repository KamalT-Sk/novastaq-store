<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'number', 'user_id', 'customer_name', 'email', 'phone', 'address', 'city', 'state', 'notes',
        'subtotal', 'delivery_fee', 'total', 'status', 'payment_status', 'payment_provider', 'payment_reference', 'paid_at',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    protected static function booted(): void
    {
        static::creating(function (Order $o) {
            $o->number ??= 'NV-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
