<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'name', 'variant_name',
        'account_details', 'delivery_type', 'digital_content', 'digital_file',
        'digital_instructions', 'unit_price', 'quantity', 'line_total',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function displayName(): string
    {
        if (! empty($this->variant_name)) {
            return "{$this->name} ({$this->variant_name})";
        }

        return $this->name;
    }

    public function digitalKey()
    {
        return $this->hasOne(ProductDigitalKey::class);
    }

    public function digitalKeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductDigitalKey::class, 'order_item_id');
    }

    /**
     * Returns all assigned digital links or codes for this item as a list.
     * Supports single keys, multiple pooled keys, and multi-line content.
     *
     * @return list<string>
     */
    public function digitalLinks(): array
    {
        $sanitize = static fn (string $v): string => trim((string) preg_replace('/(?:[\s\-_]+(?:\d{1,3}[\.\)\:\-]|\[\d{1,3}\])|\[\d{1,3}\]|(?<=[^\d\s])\d{1,3}[\.\)\:]|\.)$/u', '', trim($v)));

        $keys = $this->digitalKeys()->pluck('key_content')->all();
        if (! empty($keys)) {
            return array_values(array_filter(array_map($sanitize, $keys)));
        }

        if (! empty($this->digital_content)) {
            $lines = preg_split('/[\r\n]+/', trim($this->digital_content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $cleaned = array_values(array_filter(array_map($sanitize, $lines)));
            if (! empty($cleaned)) {
                return $cleaned;
            }

            $single = $sanitize($this->digital_content);

            return $single !== '' ? [$single] : [];
        }

        return [];
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
        if (! empty($this->digital_file)) {
            return true;
        }

        $links = $this->digitalLinks();
        if (empty($links)) {
            return false;
        }

        // If product dispenses from a pool, make sure full ordered quantity is delivered
        if ($this->product && $this->product->digital_type === 'pool') {
            return count($links) >= (int) $this->quantity;
        }

        return true;
    }

    public function getDigitalContentAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim((string) preg_replace('/(?:[\s\-_]+(?:\d{1,3}[\.\)\:\-]|\[\d{1,3}\])|\[\d{1,3}\]|(?<=[^\d\s])\d{1,3}[\.\)\:]|\.)$/u', '', trim($value)));
    }

    public function setDigitalContentAttribute(?string $value): void
    {
        $this->attributes['digital_content'] = $value === null
            ? null
            : trim((string) preg_replace('/(?:[\s\-_]+(?:\d{1,3}[\.\)\:\-]|\[\d{1,3}\])|\[\d{1,3}\]|(?<=[^\d\s])\d{1,3}[\.\)\:]|\.)$/u', '', trim($value)));
    }
}
