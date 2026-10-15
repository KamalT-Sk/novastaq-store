<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Handles transactional & broadcast emails for Novastaq Store.
 * Supports Mailhive Send API, Custom SMTP (cPanel, Gmail, Resend, etc.), or Laravel System default.
 * Captures detailed error feedback to assist admin diagnosis without breaking execution.
 */
class Mailhive
{
    protected ?string $lastError = null;

    /**
     * Get the error message from the most recent send attempt (if any).
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Active mail driver name: 'mailhive', 'smtp', or 'system'.
     */
    public function activeDriver(): string
    {
        $configured = Setting::get('mail_driver');
        if ($configured && in_array($configured, ['mailhive', 'smtp', 'system'], true)) {
            return $configured;
        }

        // Auto-detect based on configured credentials
        $mailhiveKey = Setting::get('mailhive_api_key') ?: config('services.mailhive.key');
        if (! empty($mailhiveKey)) {
            return 'mailhive';
        }

        $smtpHost = Setting::get('smtp_host') ?: config('mail.mailers.smtp.host');
        $smtpUser = Setting::get('smtp_username') ?: config('mail.mailers.smtp.username');
        if (! empty($smtpHost) && ! empty($smtpUser)) {
            return 'smtp';
        }

        return 'mailhive';
    }

    /**
     * Whether the active driver has the necessary credentials to attempt sending.
     */
    public function isConfigured(): bool
    {
        $driver = $this->activeDriver();
        if ($driver === 'mailhive') {
            $key = Setting::get('mailhive_api_key') ?: config('services.mailhive.key');

            return ! empty($key);
        }

        if ($driver === 'smtp') {
            $host = Setting::get('smtp_host') ?: config('mail.mailers.smtp.host');
            $user = Setting::get('smtp_username') ?: config('mail.mailers.smtp.username');

            return ! empty($host) && ! empty($user);
        }

        return true;
    }

    /**
     * Human-readable label for the active mail driver.
     */
    public function getDriverLabel(): string
    {
        return match ($this->activeDriver()) {
            'mailhive' => 'Mailhive Send API',
            'smtp' => 'Custom SMTP Server',
            'system' => 'System Mailer (.env)',
            default => 'Auto-detect',
        };
    }

    /**
     * Default From Address.
     */
    public function fromAddress(): string
    {
        return (string) (Setting::get('mail_from_address') ?: config('mail.from.address', 'hello@novastaq.com'));
    }

    /**
     * Default From Name.
     */
    public function fromName(): string
    {
        return (string) (Setting::get('mail_from_name') ?: config('mail.from.name', 'Novastaq Store'));
    }

    /**
     * Send an email to one or multiple recipients.
     */
    public function send(string|array $to, string $subject, string $html, ?string $replyTo = null, array $tags = []): bool
    {
        $this->lastError = null;
        $driver = $this->activeDriver();

        if ($driver === 'smtp') {
            return $this->sendViaSmtp($to, $subject, $html, $replyTo);
        }

        if ($driver === 'system') {
            return $this->sendViaSystem($to, $subject, $html, $replyTo);
        }

        return $this->sendViaMailhive($to, $subject, $html, $replyTo, $tags);
    }

    /**
     * Deliver via Mailhive Send API (HTTP POST).
     */
    protected function sendViaMailhive(string|array $to, string $subject, string $html, ?string $replyTo = null, array $tags = []): bool
    {
        $key = (string) (Setting::get('mailhive_api_key') ?: config('services.mailhive.key'));
        $url = (string) (Setting::get('mailhive_api_url') ?: config('services.mailhive.url', 'https://api.mailhive.africa/v1'));

        $fromAddress = $this->fromAddress();
        $fromName = $this->fromName();
        $fromFormatted = "{$fromName} <{$fromAddress}>";

        $replyTo = $replyTo ?: (Setting::get('mail_reply_to') ?: config('store.support_email', $fromAddress));

        // Mailhive API requires 'tags' to be a dictionary/object (e.g. {"type": "diagnostic-test"}),
        // not a sequential list. Ensure any list passed is converted to key-value pairs.
        $formattedTags = null;
        if (! empty($tags)) {
            if (array_is_list($tags)) {
                $formattedTags = [];
                foreach ($tags as $idx => $tag) {
                    $k = count($tags) === 1 ? 'type' : 'tag_'.($idx + 1);
                    $formattedTags[$k] = (string) $tag;
                }
            } else {
                $formattedTags = array_map(fn ($val) => (string) $val, $tags);
            }
        }

        $payload = array_filter([
            'from' => $fromFormatted,
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/tr|\/h\d)>/i', "\n", $html)))),
            'reply_to' => $replyTo,
            'tags' => $formattedTags,
        ], fn ($value) => $value !== null);

        if ($key === '') {
            $this->lastError = 'Mailhive API Key is not configured. Go to Admin > Emails > Settings to add your API key or configure an SMTP server.';
            Log::info('Mailhive key not set; email not sent', ['to' => $to, 'subject' => $subject]);

            return false;
        }

        try {
            $res = Http::withToken($key)->acceptJson()->timeout(15)
                ->post(rtrim($url, '/').'/send/emails', $payload);

            if (! $res->successful()) {
                $err = $res->json('message') ?? $res->body() ?: "HTTP {$res->status()}";
                $this->lastError = "Mailhive API rejected request (HTTP {$res->status()}): {$err}";
                Log::warning('Mailhive send failed', ['status' => $res->status(), 'body' => $res->body(), 'subject' => $subject]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            $this->lastError = 'Mailhive connection failed: '.$e->getMessage();
            report($e);

            return false;
        }
    }

    /**
     * Deliver via SMTP server (cPanel, Gmail, Resend, Brevo, etc.).
     */
    protected function sendViaSmtp(string|array $to, string $subject, string $html, ?string $replyTo = null): bool
    {
        $host = (string) (Setting::get('smtp_host') ?: config('mail.mailers.smtp.host'));
        $port = (int) (Setting::get('smtp_port') ?: config('mail.mailers.smtp.port', 465));
        $username = (string) (Setting::get('smtp_username') ?: config('mail.mailers.smtp.username'));
        $password = (string) (Setting::get('smtp_password') ?: config('mail.mailers.smtp.password'));
        $encryption = (string) (Setting::get('smtp_encryption') ?: config('mail.mailers.smtp.scheme', 'ssl'));

        $fromAddress = $this->fromAddress();
        $fromName = $this->fromName();
        $replyTo = $replyTo ?: (Setting::get('mail_reply_to') ?: config('store.support_email', $fromAddress));

        if (empty($host) || empty($username)) {
            $this->lastError = 'SMTP Host or Username is not configured in Admin > Emails > Settings.';

            return false;
        }

        try {
            config([
                'mail.mailers.runtime_smtp' => [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => $port,
                    'encryption' => ($encryption === 'none' || $encryption === '') ? null : $encryption,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 15,
                ],
            ]);

            if (app()->bound('mail.manager')) {
                Mail::purge('runtime_smtp');
            }

            Mail::mailer('runtime_smtp')->html($html, function ($message) use ($to, $subject, $fromAddress, $fromName, $replyTo) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });

            return true;
        } catch (Throwable $e) {
            $this->lastError = 'SMTP error: '.$e->getMessage();
            Log::warning('SMTP send failed', ['error' => $e->getMessage(), 'subject' => $subject, 'to' => $to]);
            report($e);

            return false;
        }
    }

    /**
     * Deliver via Laravel's default configured mailer.
     */
    protected function sendViaSystem(string|array $to, string $subject, string $html, ?string $replyTo = null): bool
    {
        $fromAddress = $this->fromAddress();
        $fromName = $this->fromName();
        $replyTo = $replyTo ?: (Setting::get('mail_reply_to') ?: config('store.support_email', $fromAddress));

        try {
            Mail::html($html, function ($message) use ($to, $subject, $fromAddress, $fromName, $replyTo) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });

            return true;
        } catch (Throwable $e) {
            $this->lastError = 'System mailer error: '.$e->getMessage();
            report($e);

            return false;
        }
    }
}
