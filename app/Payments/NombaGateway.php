<?php

namespace App\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NombaGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly ?string $accountId,
        private readonly string $baseUrl = 'https://api.nomba.com',
        private readonly ?string $webhookSecret = null,
    ) {}

    public function name(): string
    {
        return 'nomba';
    }

    public function label(): string
    {
        return 'Nomba';
    }

    public function subtitle(): string
    {
        return 'Cards, Bank Transfer, USSD & QR';
    }

    public function badge(): string
    {
        return 'Cards / Transfer';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret) && ! empty($this->accountId);
    }

    /**
     * Obtains an OAuth2 bearer token from Nomba using client credentials.
     * Caches the token for 25 minutes (tokens expire in 30 minutes).
     */
    public function getAccessToken(): string
    {
        if (! $this->clientId || ! $this->clientSecret || ! $this->accountId) {
            throw new RuntimeException('Nomba credentials (NOMBA_CLIENT_ID, NOMBA_CLIENT_SECRET, NOMBA_ACCOUNT_ID) are not configured in .env');
        }

        $cacheKey = 'nomba_access_token_'.md5($this->clientId.$this->accountId);

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $response = Http::acceptJson()->asJson()->timeout(15)
            ->withHeaders(['accountId' => $this->accountId])
            ->post(rtrim($this->baseUrl, '/').'/v1/auth/token/issue', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        if (! $response->successful()) {
            $msg = $response->json('message') ?? $response->json('description') ?? 'Token issuance failed ('.$response->status().')';
            throw new RuntimeException('Nomba auth: '.$msg);
        }

        $token = $response->json('data.access_token')
            ?? $response->json('data.token')
            ?? $response->json('access_token');

        if (! $token) {
            throw new RuntimeException('Nomba did not return an access token');
        }

        $expiresIn = (int) ($response->json('data.expires_in') ?? $response->json('expires_in') ?? 1800);
        $ttl = max(60, $expiresIn - 300);

        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): string
    {
        $token = $this->getAccessToken();
        $amountNaira = number_format($amountKobo / 100, 2, '.', '');

        $payload = [
            'order' => [
                'orderReference' => $reference,
                'amount' => $amountNaira,
                'currency' => 'NGN',
                'customerEmail' => $email,
                'callbackUrl' => $callbackUrl,
            ],
        ];

        $response = $this->httpWithAuth($token)->post(rtrim($this->baseUrl, '/').'/v1/checkout/order', $payload);

        if (! $response->successful()) {
            $msg = $response->json('message') ?? $response->json('description') ?? 'Checkout initialization failed ('.$response->status().')';
            throw new RuntimeException('Nomba checkout: '.$msg);
        }

        $link = $response->json('data.checkoutLink')
            ?? $response->json('data.checkout_link')
            ?? $response->json('checkoutLink')
            ?? $response->json('data.url');

        if (! $link) {
            throw new RuntimeException('Nomba did not return a valid checkout link');
        }

        return $link;
    }

    public function verify(string $reference): VerifiedPayment
    {
        $token = $this->getAccessToken();

        // 1. Query transaction lookup endpoint by orderReference
        $response = $this->httpWithAuth($token)->get(rtrim($this->baseUrl, '/').'/v1/transactions/accounts/single', [
            'orderReference' => $reference,
        ]);

        $data = $response->json('data') ?? [];

        // 2. Fallback: try querying /v1/checkout/order/{reference}
        if (! $response->successful() || empty($data)) {
            $orderResponse = $this->httpWithAuth($token)->get(rtrim($this->baseUrl, '/').'/v1/checkout/order/'.$reference);
            if ($orderResponse->successful()) {
                $data = $orderResponse->json('data') ?? [];
            }
        }

        if (empty($data)) {
            throw new RuntimeException('Nomba verification failed: '.($response->json('message') ?? 'record not found').' ('.$response->status().')');
        }

        $status = strtoupper((string) (
            $data['status']
            ?? ($data['order']['status'] ?? null)
            ?? ($data['transaction']['status'] ?? null)
            ?? ''
        ));

        $paid = in_array($status, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID'], true);

        $amountNaira = (float) (
            $data['amount']
            ?? ($data['order']['amount'] ?? null)
            ?? ($data['transaction']['amount'] ?? null)
            ?? 0
        );

        $amountKobo = (int) round($amountNaira * 100);

        return new VerifiedPayment($paid, $amountKobo, $reference, $data);
    }

    public function isValidWebhook(Request $request): bool
    {
        $signature = (string) (
            $request->header('nomba-signature')
            ?? $request->header('x-nomba-signature')
            ?? $request->header('Nomba-Signature')
            ?? $request->header('nomba-sig-value')
        );

        if ($signature === '') {
            return false;
        }

        $payload = $request->getContent();
        $secret = $this->webhookSecret ?: $this->clientSecret;

        if (! $secret) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    private function httpWithAuth(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->withHeaders(['accountId' => (string) $this->accountId])
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }
}
