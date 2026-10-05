<?php

namespace App\Payments;

final class VerifiedPayment
{
    /** @param  array<string, mixed>  $raw */
    public function __construct(
        public readonly bool $paid,
        public readonly int $amountKobo,
        public readonly string $reference,
        public readonly array $raw = [],
    ) {}
}
