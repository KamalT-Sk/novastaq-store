@extends('emails.layout')

@section('title', 'We received your message — Novastaq')
@section('badge', 'Novastaq')
@section('preheader', 'Thanks for contacting Novastaq. We received your message and will reply within 24 to 48 hours.')

@section('body')
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Message Received</p>
    <h1 class="h1" style="margin:0 0 16px;font-size:24px;font-weight:700;color:#0b0b0f;line-height:1.3;">Thanks for reaching out{{ $name !== 'there' ? ', '.$name : '' }}!</h1>

    <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#3f3f46;">
        We've received your enquiry regarding <strong>{{ $service ?: 'our technology services' }}</strong>. A member of our team is reviewing your requirements and will reply within <strong>24 to 48 hours</strong> with next steps and an honest assessment.
    </p>

    {{-- Details summary card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;background:#fafafa;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#71717a;">Summary of Your Submission</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @if ($service)
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:110px;vertical-align:top;">Service:</td>
                            <td style="padding:4px 0;font-size:14px;font-weight:600;color:#0b0b0f;">{{ $service }}</td>
                        </tr>
                    @endif
                    @if (!empty($enquiry->budget) && $enquiry->budget !== 'Not given')
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:110px;vertical-align:top;">Budget:</td>
                            <td style="padding:4px 0;font-size:14px;color:#0b0b0f;">{{ $enquiry->budget }}</td>
                        </tr>
                    @endif
                    @if (!empty($enquiry->company) && $enquiry->company !== 'Not given')
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:110px;vertical-align:top;">Company:</td>
                            <td style="padding:4px 0;font-size:14px;color:#0b0b0f;">{{ $enquiry->company }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:110px;vertical-align:top;">Your Message:</td>
                        <td style="padding:4px 0;font-size:13px;color:#52525b;white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit($enquiry->message, 300) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#52525b;">
        Need immediate assistance or want to chat with our engineering team right away? You can also reach us directly on WhatsApp.
    </p>

    <p style="margin:0;font-size:14px;color:#52525b;line-height:1.6;">
        Warm regards,<br>
        <strong style="color:#0b0b0f;">The Novastaq Team</strong><br>
        <span style="font-size:12px;color:#71717a;">Software, Web3 &amp; Tech Procurement</span>
    </p>
@endsection

@section('reason', 'You received this confirmation because you contacted Novastaq via novastaq.com.')

