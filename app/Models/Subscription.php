<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'email', 'status', 'provider', 'provider_subscription_code',
        'provider_customer_code', 'provider_email_token', 'payment_reference', 'started_at', 'next_payment_at', 'cancelled_at',
    ];

    protected $casts = ['started_at' => 'datetime', 'next_payment_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public const STATUSES = ['pending', 'active', 'non-renewing', 'attention', 'cancelled'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
