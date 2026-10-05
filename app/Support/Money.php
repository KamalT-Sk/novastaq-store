<?php

namespace App\Support;

final class Money
{
    /** Kobo to a display string: 125000000 -> "₦1,250,000"; kobo shown only when present. */
    public static function naira(int|float|null $kobo): string
    {
        $naira = ((int) $kobo) / 100;

        return '₦'.number_format($naira, fmod($naira, 1) ? 2 : 0);
    }

    /** "1,250,000.50" (from a form) to kobo, without float rounding errors. */
    public static function toKobo(string|int|float $naira): int
    {
        $clean = preg_replace('/[^\d.]/', '', (string) $naira);
        [$whole, $frac] = array_pad(explode('.', $clean, 2), 2, '0');

        return ((int) $whole) * 100 + (int) str_pad(substr($frac, 0, 2), 2, '0');
    }
}
