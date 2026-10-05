<?php

namespace App\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements PaymentGateway
{
    public function __construct(private readonly ?string $secretKey) {}

    public function name(): string
    {
        return 'paystack';
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = [], ?string $planCode = null): string
    {
        $payload = array_filter([
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
            'plan' => $planCode, // with a plan, Paystack charges the plan's amount and subscribes the customer
        ], fn ($v) => $v !== null);

        return $this->call('post', '/transaction/initialize', $payload)['authorization_url'];
    }

    public function verify(string $reference): VerifiedPayment
    {
        $data = $this->call('get', '/transaction/verify/'.rawurlencode($reference));

        return new VerifiedPayment(($data['status'] ?? null) === 'success', (int) ($data['amount'] ?? 0), $reference, $data);
    }

    public function isValidWebhook(Request $request): bool
    {
        $signature = (string) $request->header('x-paystack-signature');

        return $this->secretKey !== null && $signature !== ''
            && hash_equals(hash_hmac('sha512', $request->getContent(), $this->secretKey), $signature);
    }

    public function createPlan(string $name, int $amountKobo, string $interval): string
    {
        return $this->call('post', '/plan', ['name' => $name, 'amount' => $amountKobo, 'interval' => $interval])['plan_code'];
    }

    /** @return array<string, mixed> the response's `data` */
    private function call(string $method, string $path, array $payload = []): array
    {
        if (! $this->secretKey) {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not set in .env');
        }

        $response = $this->http()->{$method}('https://api.paystack.co'.$path, $payload);

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Paystack: '.($response->json('message') ?? 'request failed').' ('.$response->status().')');
        }

        return $response->json('data');
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->secretKey)->acceptJson()->timeout(20)->retry(2, 300, throw: false);
    }
}
