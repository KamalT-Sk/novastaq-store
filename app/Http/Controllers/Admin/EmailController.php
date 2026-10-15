<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailBroadcast;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mailhive;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailController extends Controller
{
    /**
     * Broadcast composer and campaign history.
     */
    public function broadcast(Request $request, Mailhive $mailhive): View
    {
        $loadedBroadcast = null;
        if ($cloneId = $request->query('clone') ?: $request->query('edit')) {
            $loadedBroadcast = EmailBroadcast::find($cloneId);
        }

        $allEmails = User::pluck('email')
            ->concat(Order::pluck('email'))
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter()
            ->unique();

        $registeredEmails = User::pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter()
            ->unique();

        $payingEmails = Order::where('payment_status', 'paid')
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter()
            ->unique();

        $history = EmailBroadcast::with('sender')
            ->latest()
            ->paginate(15);

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'price', 'type']);

        return view('admin.emails.broadcast', [
            'audienceCounts' => [
                'all' => $allEmails->count(),
                'registered' => $registeredEmails->count(),
                'paying' => $payingEmails->count(),
            ],
            'history' => $history,
            'loadedBroadcast' => $loadedBroadcast,
            'products' => $products,
            'isConfigured' => $mailhive->isConfigured(),
            'activeDriver' => $mailhive->activeDriver(),
            'driverLabel' => $mailhive->getDriverLabel(),
            'adminEmail' => auth()->user()->email,
        ]);
    }

    /**
     * Send test preview of the currently composed broadcast.
     */
    public function sendTest(Request $request, Mailhive $mailhive): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:20000'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'test_recipient' => ['nullable', 'email', 'max:190'],
        ]);

        if (! empty($data['button_url'])) {
            $data['button_url'] = $this->normalizeUrl($data['button_url']);
        }

        $recipient = $data['test_recipient'] ?? auth()->user()->email;

        $html = view('emails.broadcast', [
            'subject' => '[TEST PREVIEW] '.$data['subject'],
            'eyebrow' => ($data['eyebrow'] ?? null) ?: 'Test Preview',
            'headline' => $data['headline'],
            'content' => $data['content'],
            'buttonLabel' => $data['button_label'] ?? null,
            'buttonUrl' => $data['button_url'] ?? null,
        ])->render();

        $sent = $mailhive->send(
            to: $recipient,
            subject: '[TEST PREVIEW] '.$data['subject'],
            html: $html,
            replyTo: Setting::get('mail_reply_to') ?: config('store.support_email'),
            tags: ['broadcast-test']
        );

        if ($sent) {
            return back()->withInput()->with('status', "Test email sent to {$recipient} via {$mailhive->getDriverLabel()}. Check your inbox!");
        }

        $error = $mailhive->getLastError() ?: 'Please check your mail configuration in Email Settings.';

        return back()->withInput()->with('error', "Could not send test email to {$recipient}: {$error}");
    }

    /**
     * Broadcast to all or selected customers, or save as a draft.
     */
    public function sendBroadcast(Request $request, Mailhive $mailhive): RedirectResponse
    {
        $isDraft = $request->has('save_draft') || $request->input('action') === 'draft';

        $rules = [
            'audience' => ['required', Rule::in(['all', 'registered', 'paying'])],
            'subject' => ['required', 'string', 'max:190'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:20000'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'broadcast_id' => ['nullable', 'integer', 'exists:email_broadcasts,id'],
        ];

        $data = $request->validate($rules);

        if (! empty($data['button_url'])) {
            $data['button_url'] = $this->normalizeUrl($data['button_url']);
        }

        // If saving as draft
        if ($isDraft) {
            if (! empty($data['broadcast_id'])) {
                $broadcast = EmailBroadcast::find($data['broadcast_id']);
                if ($broadcast && $broadcast->isDraft()) {
                    $broadcast->update([
                        'subject' => $data['subject'],
                        'eyebrow' => $data['eyebrow'] ?? null,
                        'headline' => $data['headline'],
                        'content' => $data['content'],
                        'button_label' => $data['button_label'] ?? null,
                        'button_url' => $data['button_url'] ?? null,
                        'audience' => $data['audience'],
                    ]);

                    return redirect()->route('admin.emails.broadcast')
                        ->with('status', 'Draft campaign updated successfully!');
                }
            }

            EmailBroadcast::create([
                'subject' => $data['subject'],
                'eyebrow' => $data['eyebrow'] ?? null,
                'headline' => $data['headline'],
                'content' => $data['content'],
                'button_label' => $data['button_label'] ?? null,
                'button_url' => $data['button_url'] ?? null,
                'audience' => $data['audience'],
                'status' => 'draft',
                'recipients_count' => 0,
                'sent_by_user_id' => auth()->id(),
            ]);

            return redirect()->route('admin.emails.broadcast')
                ->with('status', 'Campaign saved as a draft. You can edit and send it anytime!');
        }

        // Check provider readiness
        if (! $mailhive->isConfigured()) {
            return back()->withInput()->with('error', 'Email server is not configured. Please go to Email Settings to configure your Mailhive API Key or SMTP server before broadcasting.');
        }

        @set_time_limit(300);

        // Fetch recipients
        $recipients = match ($data['audience']) {
            'registered' => User::pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values(),
            'paying' => Order::where('payment_status', 'paid')->pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values(),
            default => User::pluck('email')->concat(Order::pluck('email'))->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values(),
        };

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'No recipients found for the selected audience.');
        }

        $html = view('emails.broadcast', [
            'subject' => $data['subject'],
            'eyebrow' => $data['eyebrow'] ?? null,
            'headline' => $data['headline'],
            'content' => $data['content'],
            'buttonLabel' => $data['button_label'] ?? null,
            'buttonUrl' => $data['button_url'] ?? null,
        ])->render();

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            $ok = $mailhive->send(
                to: $recipient,
                subject: $data['subject'],
                html: $html,
                replyTo: Setting::get('mail_reply_to') ?: config('store.support_email'),
                tags: ['bulk-broadcast']
            );
            if ($ok) {
                $sentCount++;
            }
        }

        if ($sentCount === 0) {
            $err = $mailhive->getLastError() ?: 'The mail server rejected the delivery.';

            return back()->withInput()->with('error', "Broadcast failed. No emails could be delivered: {$err}");
        }

        // Record or update
        EmailBroadcast::create([
            'subject' => $data['subject'],
            'eyebrow' => $data['eyebrow'] ?? null,
            'headline' => $data['headline'],
            'content' => $data['content'],
            'button_label' => $data['button_label'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'audience' => $data['audience'],
            'status' => 'sent',
            'recipients_count' => $sentCount,
            'sent_at' => now(),
            'sent_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.emails.broadcast')
            ->with('status', "Broadcast successfully sent to {$sentCount} recipient(s)!");
    }

    /**
     * Email configuration & server diagnostics page.
     */
    public function settings(Mailhive $mailhive): View
    {
        return view('admin.emails.settings', [
            'activeDriver' => $mailhive->activeDriver(),
            'driverLabel' => $mailhive->getDriverLabel(),
            'isConfigured' => $mailhive->isConfigured(),
            'lastError' => $mailhive->getLastError(),
            'adminEmail' => auth()->user()->email,
            'config' => [
                'mail_driver' => Setting::get('mail_driver', 'auto'),
                'mailhive_api_key' => Setting::get('mailhive_api_key', config('services.mailhive.key')),
                'mailhive_api_url' => Setting::get('mailhive_api_url', config('services.mailhive.url', 'https://api.mailhive.africa/v1')),
                'smtp_host' => Setting::get('smtp_host', config('mail.mailers.smtp.host')),
                'smtp_port' => Setting::get('smtp_port', config('mail.mailers.smtp.port', 465)),
                'smtp_username' => Setting::get('smtp_username', config('mail.mailers.smtp.username')),
                'smtp_password' => Setting::get('smtp_password', config('mail.mailers.smtp.password')),
                'smtp_encryption' => Setting::get('smtp_encryption', config('mail.mailers.smtp.scheme', 'ssl')),
                'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address', 'hello@novastaq.com')),
                'mail_from_name' => Setting::get('mail_from_name', config('mail.from.name', 'Novastaq Store')),
                'mail_reply_to' => Setting::get('mail_reply_to', config('store.support_email', 'hello@novastaq.com')),
            ],
        ]);
    }

    /**
     * Save email server and sender settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_driver' => ['required', Rule::in(['auto', 'mailhive', 'smtp', 'system'])],
            'mailhive_api_key' => ['nullable', 'string', 'max:250'],
            'mailhive_api_url' => ['nullable', 'url', 'max:250'],
            'smtp_host' => ['nullable', 'string', 'max:250'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:250'],
            'smtp_password' => ['nullable', 'string', 'max:250'],
            'smtp_encryption' => ['nullable', Rule::in(['ssl', 'tls', 'none', ''])],
            'mail_from_address' => ['required', 'email', 'max:250'],
            'mail_from_name' => ['required', 'string', 'max:100'],
            'mail_reply_to' => ['nullable', 'email', 'max:250'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('status', 'Email settings successfully saved!');
    }

    /**
     * Send a live diagnostic ping email to verify server connectivity.
     */
    public function testDiagnostics(Request $request, Mailhive $mailhive): RedirectResponse
    {
        $data = $request->validate([
            'test_to' => ['required', 'email', 'max:190'],
        ]);

        $to = $data['test_to'];
        $driver = $mailhive->getDriverLabel();
        $now = now()->format('Y-m-d H:i:s T');

        $html = "
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:540px;margin:0 auto;padding:24px;background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;'>
                <h2 style='color:#09090b;margin-top:0;'>✅ Mail Server Diagnostic Successful</h2>
                <p style='color:#52525b;font-size:14px;line-height:1.6;'>This email confirms that your Novastaq Store email configuration is active and delivering messages successfully.</p>
                <div style='background:#f4f4f5;padding:14px;border-radius:8px;font-size:13px;color:#27272a;line-height:1.7;margin:18px 0;'>
                    <strong>Active Provider:</strong> {$driver}<br>
                    <strong>Delivered To:</strong> {$to}<br>
                    <strong>Timestamp:</strong> {$now}<br>
                    <strong>From Address:</strong> {$mailhive->fromName()} &lt;{$mailhive->fromAddress()}&gt;
                </div>
                <p style='font-size:12px;color:#a1a1aa;margin-bottom:0;'>Sent from Novastaq Admin &middot; store.novastaq.com</p>
            </div>
        ";

        $sent = $mailhive->send(
            to: $to,
            subject: 'Novastaq Store: Mail Diagnostic Test',
            html: $html,
            replyTo: Setting::get('mail_reply_to') ?: config('store.support_email'),
            tags: ['diagnostic-test']
        );

        if ($sent) {
            return back()->with('status', "Diagnostic test successful! A test email was delivered to {$to} via {$driver}.");
        }

        $err = $mailhive->getLastError() ?: 'Unknown error. Please check server logs.';

        return back()->with('error', "Diagnostic test failed: {$err}");
    }

    /**
     * Preview a past broadcast's HTML rendered email.
     */
    public function preview(EmailBroadcast $broadcast): Response
    {
        $html = view('emails.broadcast', [
            'subject' => $broadcast->subject,
            'eyebrow' => $broadcast->eyebrow,
            'headline' => $broadcast->headline,
            'content' => $broadcast->content,
            'buttonLabel' => $broadcast->button_label,
            'buttonUrl' => $broadcast->button_url,
        ])->render();

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Delete a broadcast record from history.
     */
    public function destroyBroadcast(EmailBroadcast $broadcast): RedirectResponse
    {
        $broadcast->delete();

        return back()->with('status', 'Broadcast record removed from history.');
    }

    /**
     * Overview of store automated transactional emails.
     */
    public function automated(Mailhive $mailhive): View
    {
        return view('admin.emails.automated', [
            'isConfigured' => $mailhive->isConfigured(),
            'driverLabel' => $mailhive->getDriverLabel(),
            'fromAddress' => $mailhive->fromAddress(),
        ]);
    }

    /**
     * Normalize URL to ensure absolute protocol for email links.
     */
    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/')) {
            return url($url);
        }
        if (! preg_match('~^https?://~i', $url)) {
            return 'https://'.ltrim($url, '/');
        }

        return $url;
    }
}

