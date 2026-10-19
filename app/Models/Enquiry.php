<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'service',
        'budget',
        'subject',
        'message',
        'fields',
        'ip_address',
        'status',
        'replied_at',
        'replied_by_id',
        'reply_subject',
        'reply_message',
    ];

    protected $casts = [
        'fields' => 'array',
        'replied_at' => 'datetime',
    ];

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by_id');
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isReplied(): bool
    {
        return $this->status === 'replied';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function whatsappUrl(): ?string
    {
        if (empty($this->phone)) {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $this->phone);
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '234'.substr($digits, 1);
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeReplied(Builder $query): Builder
    {
        return $query->where('status', 'replied');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")
                ->orWhere('service', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%");
        });
    }
}

