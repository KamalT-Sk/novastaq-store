<?php

namespace App\Http\Controllers;

use App\Support\Mailhive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Relays the novastaq.com contact / referral / careers forms to the inbox via Mailhive.
 * The website is static, so the email key lives here on the store's server instead.
 * Which sites may call it is set in config/cors.php; spam is kept out by a honeypot and a rate limit.
 */
class ContactController extends Controller
{
    public function store(Request $request, Mailhive $mail): JsonResponse
    {
        // Bots fill every field, people never see this one
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'reply_to' => ['required', 'email', 'max:190'],
            'fields' => ['required', 'array', 'max:20'],
            'fields.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $rows = collect($data['fields'])->map(fn ($v, $k) => '<tr><td style="padding:6px 16px 6px 0;color:#71717a;vertical-align:top;white-space:nowrap;">'.e($k).'</td><td style="padding:6px 0;white-space:pre-wrap;">'.e((string) $v).'</td></tr>')->join('');
        $html = '<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;font-size:15px;line-height:1.6;color:#0b0b0f;">'
            .'<p style="margin:0 0 4px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">From novastaq.com</p>'
            .'<h2 style="margin:0 0 16px;font-size:20px;">'.e($data['subject']).'</h2>'
            .'<table cellpadding="0" cellspacing="0">'.$rows.'</table>'
            .'<p style="margin:20px 0 0;color:#71717a;font-size:13px;">Reply to this email to answer '.e($data['reply_to']).' directly.</p></div>';

        $adminEmails = app(\App\Support\Notifier::class)->adminEmails();
        $sent = false;
        foreach ($adminEmails as $to) {
            $ok = $mail->send($to, '✉️ '.$data['subject'], $html, $data['reply_to'], ['type' => 'website-form']);
            if ($ok) {
                $sent = true;
            }
        }

        return response()->json(['ok' => $sent], $sent ? 200 : 502);
    }
}
