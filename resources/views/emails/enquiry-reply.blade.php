@extends('emails.layout')

@section('title', $replySubject)
@section('badge', 'Novastaq')
@section('preheader', \Illuminate\Support\Str::limit($replyMessage, 100))

@section('body')
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Novastaq Response</p>
    <h1 class="h1" style="margin:0 0 20px;font-size:22px;font-weight:700;color:#0b0b0f;line-height:1.3;">{{ $replySubject }}</h1>

    {{-- Admin's personalized reply --}}
    <div style="font-size:15px;line-height:1.75;color:#18181b;white-space:pre-wrap;margin:0 0 28px;">{{ $replyMessage }}</div>

    {{-- Reference to original message --}}
    <div style="margin:28px 0 0;padding:16px 20px;background:#fafafa;border:1px solid #e4e4e7;border-left:3px solid #6366f1;border-radius:0 10px 10px 0;">
        <p style="margin:0 0 6px;font-size:12px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:.06em;">
            Your original enquiry ({{ $enquiry->created_at->format('M j, Y') }}):
        </p>
        <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#0b0b0f;">{{ $enquiry->subject }}</p>
        <div style="font-size:13px;line-height:1.6;color:#71717a;white-space:pre-wrap;">{{ $enquiry->message }}</div>
    </div>

    <p style="margin:28px 0 0;font-size:14px;color:#52525b;line-height:1.6;">
        Best regards,<br>
        <strong style="color:#0b0b0f;">{{ $repliedBy ? $repliedBy->name . ' · Novastaq' : 'The Novastaq Team' }}</strong><br>
        <a href="mailto:{{ config('store.support_email', 'hello@novastaq.com') }}" style="color:#4f46e5;text-decoration:none;font-size:13px;">{{ config('store.support_email', 'hello@novastaq.com') }}</a>
    </p>
@endsection

@section('reason', 'You received this email in response to your enquiry on novastaq.com.')

