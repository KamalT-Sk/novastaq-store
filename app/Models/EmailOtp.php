<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EmailOtp extends Model
{
    protected $fillable = [
        'email',
        'otp_hash',
        'purpose',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Generate a new 6-digit OTP code for an email and purpose.
     * Deletes any existing OTPs for the same email/purpose, hashes the new code,
     * and returns the plain 6-digit string so it can be emailed.
     */
    public static function generate(string $email, string $purpose = 'registration', int $ttlMinutes = 15): string
    {
        $email = strtolower(trim($email));

        static::where('email', $email)
            ->where('purpose', $purpose)
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        static::create([
            'email' => $email,
            'otp_hash' => Hash::make($code),
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return $code;
    }

    /**
     * Verifies the provided 6-digit OTP code.
     * Protects against brute-force (max 5 attempts).
     * Deletes the OTP once successfully verified (one-time use).
     */
    public static function verify(string $email, string $code, string $purpose = 'registration'): bool
    {
        $email = strtolower(trim($email));
        $code = trim($code);

        $record = static::where('email', $email)
            ->where('purpose', $purpose)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record) {
            return false;
        }

        if ($record->attempts >= 5) {
            $record->delete();

            return false;
        }

        $record->increment('attempts');

        if (Hash::check($code, $record->otp_hash)) {
            $record->delete();

            return true;
        }

        return false;
    }

    /**
     * Delete any pending OTPs for an email and purpose.
     */
    public static function clear(string $email, string $purpose = 'registration'): void
    {
        static::where('email', strtolower(trim($email)))
            ->where('purpose', $purpose)
            ->delete();
    }
}

