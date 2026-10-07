@php($site = rtrim(config('app.url'), '/'))
@php($main = rtrim(config('store.main_site'), '/'))
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Novastaq Store')</title>
    <style>
        @media (max-width: 600px) {
            .card { border-radius: 0 !important; border-left: 0 !important; border-right: 0 !important; }
            .pad { padding-left: 22px !important; padding-right: 22px !important; }
            .h1 { font-size: 24px !important; }
            .stack { display: block !important; width: auto !important; text-align: left !important; }
            .stack-btn { padding-top: 0 !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;-webkit-text-size-adjust:100%;">
    {{-- Inbox preview line --}}
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f4f5;">@yield('preheader')&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;">
        <tr><td align="center" style="padding:28px 12px 40px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card" style="max-width:600px;background:#ffffff;border:1px solid #e4e4e7;border-radius:18px;overflow:hidden;">
                {{-- accent line --}}
                <tr><td style="height:4px;background:#4f46e5;font-size:0;line-height:0;">&nbsp;</td></tr>

                {{-- header --}}
                <tr><td class="pad" style="padding:26px 36px 22px;border-bottom:1px solid #f0f0f2;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                        <td valign="middle">
                            <a href="{{ $site }}" style="text-decoration:none;">
                                <img src="{{ $site }}/logo.png" width="124" alt="Novastaq" style="display:block;width:124px;height:auto;border:0;">
                            </a>
                        </td>
                        <td valign="middle" align="right" style="font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#a1a1aa;">Store</td>
                    </tr></table>
                </td></tr>

                {{-- body --}}
                <tr><td class="pad" style="padding:32px 36px 8px;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#3f3f46;">
                    @yield('body')
                </td></tr>

                {{-- help box --}}
                <tr><td class="pad" style="padding:24px 36px 32px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fafafa;border:1px solid #f0f0f2;border-radius:14px;">
                        <tr>
                            <td class="stack" style="padding:18px 20px;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#52525b;">
                                <strong style="color:#0b0b0f;">Need help?</strong><br>Reply to this email or chat with us. We usually answer within a few hours.
                            </td>
                            <td class="stack stack-btn" align="right" style="padding:18px 20px;white-space:nowrap;">
                                <a href="{{ config('store.whatsapp_url') }}" style="display:inline-block;background:#ffffff;border:1px solid #e4e4e7;color:#0b0b0f;text-decoration:none;padding:10px 16px;border-radius:999px;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;font-weight:600;">Chat on WhatsApp</a>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            {{-- footer --}}
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
                <tr><td align="center" style="padding:24px 16px 0;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.8;color:#71717a;">
                    <a href="{{ route('shop') }}" style="color:#52525b;text-decoration:none;">Shop</a> &nbsp;·&nbsp;
                    <a href="{{ route('dashboard') }}" style="color:#52525b;text-decoration:none;">My orders</a> &nbsp;·&nbsp;
                    <a href="{{ $main }}/returns" style="color:#52525b;text-decoration:none;">Returns</a> &nbsp;·&nbsp;
                    <a href="{{ $main }}/terms" style="color:#52525b;text-decoration:none;">Terms</a> &nbsp;·&nbsp;
                    <a href="{{ $main }}/privacy" style="color:#52525b;text-decoration:none;">Privacy</a>
                    <br>
                    <a href="https://x.com/NovastaqHQ" style="color:#52525b;text-decoration:none;">X</a> &nbsp;·&nbsp;
                    <a href="https://instagram.com/novastaq" style="color:#52525b;text-decoration:none;">Instagram</a> &nbsp;·&nbsp;
                    <a href="https://facebook.com/NovastaqHQ" style="color:#52525b;text-decoration:none;">Facebook</a> &nbsp;·&nbsp;
                    <a href="mailto:{{ config('store.support_email') }}" style="color:#52525b;text-decoration:none;">{{ config('store.support_email') }}</a>
                    <br><br>
                    <span style="color:#a1a1aa;">© {{ date('Y') }} Novastaq Technologies Inc. · Software, Web3 &amp; tech procurement<br>@yield('reason', 'You received this email because of your activity on store.novastaq.com.')</span>
                </td></tr>
            </table>

        </td></tr>
    </table>
</body>
</html>
