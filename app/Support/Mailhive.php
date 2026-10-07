<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends email through Mailhive Send (the provider hosting novastaq.com mail).
 * API: POST https://api.mailhive.africa/v1/send/emails with a Bearer key.
 * Without MAILHIVE_API_KEY it logs instead of sending, so nothing breaks.
 * Never throws: an email problem must not stop a payment or an order update.
 */
class Mailhive
{
    public function send(string|array $to, string $subject, string $html, ?string $replyTo = null, array $tags = []): bool
    {
        $key = (string) config('services.mailhive.key');
        $payload = array_filter([
            'from' => config('services.mailhive.from'),
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/tr|\/h\d)>/i', "\n", $html)))),
            'reply_to' => $replyTo,
            'tags' => $tags ?: null,
        ]);

        if ($key === '') {
            Log::info('Mailhive key not set; email not sent', ['to' => $to, 'subject' => $subject]);

            return false;
        }

        try {
            $res = Http::withToken($key)->acceptJson()->timeout(15)
                ->post(rtrim((string) config('services.mailhive.url'), '/').'/send/emails', $payload);
            if (! $res->successful()) {
                Log::warning('Mailhive send failed', ['status' => $res->status(), 'body' => $res->body(), 'subject' => $subject]);
            }

            return $res->successful();
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
