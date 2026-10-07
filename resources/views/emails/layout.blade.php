<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0b0b0f;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 12px;">
<tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e4e4e7;border-radius:16px;">
        <tr><td style="padding:28px 32px 0;">
            <img src="{{ rtrim(config('app.url'), '/') }}/logo.png" alt="Novastaq" height="20" style="height:20px;width:auto;display:block;">
        </td></tr>
        <tr><td style="padding:24px 32px 32px;font-size:15px;line-height:1.6;">
            @yield('body')
        </td></tr>
    </table>
    <p style="max-width:560px;margin:16px auto 0;font-size:12px;line-height:1.6;color:#71717a;">
        Novastaq Technologies Inc · <a href="{{ config('app.url') }}" style="color:#71717a;">store.novastaq.com</a> ·
        <a href="{{ config('store.whatsapp_url') }}" style="color:#71717a;">WhatsApp</a> ·
        <a href="mailto:{{ config('store.support_email') }}" style="color:#71717a;">{{ config('store.support_email') }}</a>
    </p>
</td></tr>
</table>
</body>
</html>
