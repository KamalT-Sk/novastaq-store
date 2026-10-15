<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailBroadcast extends Model
{
    protected $fillable = [
        'subject',
        'eyebrow',
        'headline',
        'content',
        'button_label',
        'button_url',
        'audience',
        'status',
        'recipients_count',
        'sent_at',
        'sent_by_user_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'registered' => 'Registered users only',
            'paying' => 'Paying customers only',
            default => 'All customers & buyers',
        };
    }

    public function isDraft(): bool
    {
        return ($this->status ?? 'sent') === 'draft';
    }

    public function isSent(): bool
    {
        return ($this->status ?? 'sent') === 'sent';
    }
}
