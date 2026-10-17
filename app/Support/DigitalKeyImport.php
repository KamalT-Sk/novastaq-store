<?php

namespace App\Support;

use App\Models\Product;

final class DigitalKeyImport
{
    /** Default mode preserves every nonblank line, including punctuation. */
    public static function parse(string $raw, string $mode = 'lines'): array
    {
        $lines = preg_split('/\R/u', $raw) ?: [];
        $keys = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if ($mode !== 'lines') {
                $line = preg_replace('/^(?:\d+[.)\]:-]|\[\d+\]|[*•-])\s+/u', '', $line);
            }
            if ($mode === 'wrapped') {
                if (preg_match('/^(?:\d+[.)\]:-]|\[\d+\]|[*•-])$/u', $line)) {
                    continue;
                }
                $parts = preg_split('/(?=https?:\/\/)/i', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($parts as $part) {
                    if (preg_match('#^https?://#i', $part)) {
                        $keys[] = trim($part);
                    } elseif ($keys !== []) {
                        $keys[array_key_last($keys)] .= trim($part);
                    } else {
                        $keys[] = $part;
                    }
                }
            } else {
                $keys[] = $line;
            }
        }

        return array_values(array_filter($keys, fn ($key) => $key !== ''));
    }

    public static function preview(?Product $product, string $raw, string $mode = 'lines'): array
    {
        $keys = self::parse($raw, $mode);
        $existing = $product?->exists
            ? $product->digitalKeys()->whereIn('key_content', $keys)->pluck('key_content')->all() : [];
        $accepted = [];
        $duplicates = [];
        $rejected = [];
        foreach ($keys as $key) {
            if (mb_strlen($key) > 5000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $key)) {
                $rejected[] = $key;
            } elseif (in_array($key, $existing, true) || in_array($key, $accepted, true)) {
                $duplicates[] = $key;
            } else {
                $accepted[] = $key;
            }
        }

        return ['keys' => $accepted, 'duplicates' => $duplicates, 'rejected' => $rejected];
    }
}
