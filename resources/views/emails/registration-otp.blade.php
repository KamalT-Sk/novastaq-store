@extends('emails.layout')
@section('title', 'Verify your email')
@section('preheader', 'Your 6-digit Novastaq Store verification code is '.$otp.'. Valid for '.$minutes.' minutes.')
@section('reason', 'You received this email because an account was registered with your email on store.novastaq.com.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Verification Code',
        'title' => 'Verify your email address',
        'intro' => 'Hi '.(\Illuminate\Support\Str::before($name, ' ') ?: 'there').', use the 6-digit code below to finish setting up your Novastaq Store account.',
    ])

    <div style="margin:24px 0;padding:24px;background:#f4f4f5;border-radius:14px;text-align:center;">
        <span style="font-family:monospace,'SFMono-Regular',Consolas,Courier,monospace;font-size:36px;font-weight:700;letter-spacing:0.25em;color:#0b0b0f;">{{ $otp }}</span>
        <p style="margin:10px 0 0;font-size:12px;color:#71717a;">This code will expire in {{ $minutes }} minutes.</p>
    </div>

    <p style="margin:16px 0 0;font-size:13px;line-height:1.5;color:#71717a;">If you didn't create an account with Novastaq Store, please disregard this email.</p>
@endsection

