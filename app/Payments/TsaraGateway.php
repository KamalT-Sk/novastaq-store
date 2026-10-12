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

        // 1. Fetch full transaction details (returns amount, status, reference)
        $response = $this->httpWithSecret()->get(rtrim($this->baseUrl, '/').'/checkout', [
            'trx_id' => $reference,
        ]);

        $data = $response->json('data') ?? [];

        // 2. Fallback to /checkout/status if /checkout did not return data
        if (! $response->successful() || empty($data)) {
            $statusResponse = $this->httpWithSecret()->get(rtrim($this->baseUrl, '/').'/checkout/status', [
                'trx_id' => $reference,
                'reference' => $reference,
            ]);
            if ($statusResponse->successful()) {
                $data = $statusResponse->json('data') ?? [];
            }
        }

        if (empty($data)) {
            throw new RuntimeException('Tsara verification failed: '.($response->json('message') ?? 'request failed').' ('.$response->status().')');
        }

        $status = strtolower((string) ($data['status'] ?? $response->json('status') ?? ''));
        $paid = in_array($status, ['success', 'successful', 'completed', 'paid'], true);

        // Amount returned by Tsara is in standard currency units (Naira)
        $amountNaira = (float) (
            $data['amount']
            ?? $data['fiat_amount']
            ?? ($data['account']['crypto']['fiat_amount'] ?? null)
            ?? ($data['sender']['amount'] ?? null)
            ?? 0
        );

        if ($amountNaira == 0 && ! empty($data['trx_data'])) {
            $trxData = is_string($data['trx_data']) ? json_decode($data['trx_data'], true) : $data['trx_data'];
            $amountNaira = (float) ($trxData['amount'] ?? $trxData['sender']['amount'] ?? 0);
        }

        $amountKobo = (int) round($amountNaira * 100);

        return new VerifiedPayment($paid, $amountKobo, $reference, $data);
    }

    public function isValidWebhook(Request $request): bool
    {
        $signature = (string) ($request->header('x-tsara-signature') ?? $request->header('X-Tsara-Signature'));

        if ($signature === '') {
            return false;
        }

        $payload = $request->getContent();

        // Check against webhook_secret if configured
        $webhookSecret = config('services.tsara.webhook_secret');
        if ($webhookSecret && hash_equals(hash_hmac('sha512', $payload, $webhookSecret), $signature)) {
            return true;
        }

        // Check against secret_key
        return $this->secretKey !== null
            && hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
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
