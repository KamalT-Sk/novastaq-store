<?php

namespace App\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TsaraGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $publicKey,
        private readonly ?string $secretKey,
        private readonly string $baseUrl = 'https://api.tsara.ng/v1',
    ) {}

    public function name(): string
    {
        return 'tsara';
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): string
    {
        if (! $this->publicKey) {
            throw new RuntimeException('TSARA_PUBLIC_KEY is not set in .env');
        }

        $amountNaira = (int) round($amountKobo / 100);

        $payload = array_filter([
            'public_key' => $this->publicKey,
            'trx_id' => $reference,
            'email' => $email,
            'amount' => $amountNaira,
            'amount_type' => 'fiat',
            'currency' => 'NGN',
            'redirect_url' => $callbackUrl,
            'success_url' => $callbackUrl,
            'cancel_url' => $metadata['cancel_url'] ?? url('/checkout'),
            'name' => $metadata['customer_name'] ?? $metadata['name'] ?? null,
            'phone' => $metadata['phone'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $response = $this->http()->post(rtrim($this->baseUrl, '/').'/checkout', $payload);

        if (! $response->successful() || (! $response->json('success') && ! $response->json('status'))) {
            throw new RuntimeException('Tsara: '.($response->json('message') ?? 'request failed').' ('.$response->status().')');
        }

        $url = $response->json('data.checkout_url') ?? $response->json('url');

        if (! $url) {
            throw new RuntimeException('Tsara did not return a valid checkout URL');
        }

        return $url;
    }

    public function verify(string $reference): VerifiedPayment
    {
        if (! $this->secretKey) {
            throw new RuntimeException('TSARA_SECRET_KEY is not set in .env');
        }

        $response = $this->httpWithSecret()->get(rtrim($this->baseUrl, '/').'/checkout/status', [
            'trx_id' => $reference,
            'reference' => $reference,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Tsara verification failed: '.($response->json('message') ?? 'request failed').' ('.$response->status().')');
        }

        $data = $response->json('data') ?? [];
        $status = strtolower((string) ($data['status'] ?? $response->json('status') ?? ''));
        $paid = in_array($status, ['success', 'successful', 'completed', 'paid'], true);

        // Amount returned by Tsara is in standard currency units (Naira)
        $amountNaira = (float) ($data['fiat_amount'] ?? $data['amount'] ?? 0);
        $amountKobo = (int) round($amountNaira * 100);

        return new VerifiedPayment($paid, $amountKobo, $reference, $data);
    }

    public function isValidWebhook(Request $request): bool
    {
        $signature = (string) ($request->header('x-tsara-signature') ?? $request->header('X-Tsara-Signature'));

        return $this->secretKey !== null && $signature !== ''
            && hash_equals(hash_hmac('sha512', $request->getContent(), $this->secretKey), $signature);
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(20)->retry(2, 300, throw: false);
    }

    private function httpWithSecret(): PendingRequest
    {
        return Http::withToken($this->secretKey)->acceptJson()->asJson()->timeout(20)->retry(2, 300, throw: false);
    }
}
