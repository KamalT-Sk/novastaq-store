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
        'subtotal', 'delivery_fee', 'total', 'status', 'payment_status', 'payment_provider', 'payment_reference', 'paid_at', 'reminded_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    // Devices go processing → shipped → delivered; services go processing → completed ("Mark as done").
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

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

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function isReminded(): bool
    {
        return $this->reminded_at !== null;
    }

    public function canBeReminded(): bool
    {
        return $this->isUnpaid() && $this->status === 'pending' && ! $this->isReminded();
    }

    /** A service order: no delivery, the admin activates it and marks it done. */
    public function isService(): bool
    {
        return $this->address === null && $this->items->contains(fn (OrderItem $i) => $i->account_details !== null);
    }

    /** A digital order: no delivery address, only digital products. */
    public function isDigital(): bool
    {
        return $this->address === null && ! $this->isService() && $this->items->isNotEmpty() && $this->items->every(fn (OrderItem $i) => $i->isDigital());
    }

    public function hasDigital(): bool
    {
        return $this->items->contains(fn (OrderItem $i) => $i->isDigital());
    }

    public function hasPhysical(): bool
    {
        return $this->items->contains(fn (OrderItem $i) => ! $i->isDigital() && $i->account_details === null);
    }

    /** Digital items that have not yet been assigned a link or file. */
    public function pendingDigitalItems(): \Illuminate\Support\Collection
    {
        return $this->items->filter(fn (OrderItem $i) => $i->isDigital() && ! $i->hasDigitalDelivery());
    }

    /** Paid and still waiting for us to act. */
    public function needsAction(): bool
    {
        return $this->isPaid() && in_array($this->status, ['processing', 'shipped'], true);
    }
}
