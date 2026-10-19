@extends('emails.layout')

@section('title', 'New Enquiry: ' . $subject)
@section('badge', 'Enquiry')
@section('preheader', 'New message from ' . ($enquiry->name ?: $replyTo) . ' on novastaq.com')

@section('body')
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Website Lead / Enquiry</p>
    <h1 class="h1" style="margin:0 0 20px;font-size:22px;font-weight:700;color:#0b0b0f;line-height:1.3;">{{ $subject }}</h1>

    {{-- Contact info summary card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;background:#fafafa;">
        <tr>
            <td style="padding:16px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @if ($enquiry->name)
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Name:</td>
                            <td style="padding:4px 0;font-size:14px;font-weight:600;color:#0b0b0f;">{{ $enquiry->name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Email:</td>
                        <td style="padding:4px 0;font-size:14px;color:#0b0b0f;">
                            <a href="mailto:{{ $replyTo }}" style="color:#4f46e5;text-decoration:none;font-weight:500;">{{ $replyTo }}</a>
                        </td>
                    </tr>
                    @if ($enquiry->phone)
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Phone / WhatsApp:</td>
                            <td style="padding:4px 0;font-size:14px;color:#0b0b0f;">
                                <span>{{ $enquiry->phone }}</span>
                                @if ($enquiry->whatsappUrl())
                                    &nbsp;·&nbsp;<a href="{{ $enquiry->whatsappUrl() }}" target="_blank" rel="noopener" style="color:#10b981;text-decoration:none;font-weight:600;font-size:12px;">Chat on WhatsApp ↗</a>
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if ($enquiry->company)
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Company:</td>
                            <td style="padding:4px 0;font-size:14px;color:#0b0b0f;">{{ $enquiry->company }}</td>
                        </tr>
                    @endif
                    @if ($enquiry->service)
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Service:</td>
                            <td style="padding:4px 0;font-size:14px;font-weight:600;color:#0b0b0f;">{{ $enquiry->service }}</td>
                        </tr>
                    @endif
                    @if ($enquiry->budget)
                        <tr>
                            <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Budget:</td>
                            <td style="padding:4px 0;font-size:14px;color:#0b0b0f;">{{ $enquiry->budget }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 16px 4px 0;color:#71717a;font-size:13px;width:120px;vertical-align:top;">Submitted:</td>
                        <td style="padding:4px 0;font-size:13px;color:#71717a;">{{ now()->format('M j, Y \a\t g:i A') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Message block --}}
    <div style="margin:0 0 28px;">
        <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">Message Content</p>
        <div style="background:#ffffff;border:1px solid #e4e4e7;border-left:4px solid #4f46e5;border-radius:8px;padding:16px 20px;font-size:15px;line-height:1.65;color:#18181b;white-space:pre-wrap;">{{ $enquiry->message }}</div>
    </div>

    {{-- Action button --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td>
                <a href="{{ route('admin.enquiries.show', $enquiry) }}" style="display:inline-block;background:#0b0b0f;color:#ffffff;text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;text-align:center;">
                    View &amp; Reply in Admin Dashboard →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;color:#71717a;line-height:1.5;">
        You can also reply directly to this email in your mail app to respond to <a href="mailto:{{ $replyTo }}" style="color:#4f46e5;text-decoration:none;">{{ $replyTo }}</a>.
    </p>
@endsection

@section('reason', 'Internal administrative notification for the Novastaq team.')

