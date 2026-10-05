<?php

namespace App\Payments;

use Illuminate\Http\Request;

/**
 * What the store needs from a payment provider. Paystack implements it today;
 * a second provider is a second class bound in AppServiceProvider.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Start a payment and return the URL to send the customer to.
     * Pass $planCode to start a recurring subscription instead of a one-off charge.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = [], ?string $planCode = null): string;

    /** Ask the provider whether $reference was paid, and for how much. */
    public function verify(string $reference): VerifiedPayment;

    /** True when a webhook request really came from the provider. */
    public function isValidWebhook(Request $request): bool;

    /** Create a recurring plan; returns the provider's plan code. */
    public function createPlan(string $name, int $amountKobo, string $interval): string;
}
