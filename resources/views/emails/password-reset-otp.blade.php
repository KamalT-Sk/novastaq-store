@extends('emails.layout')
@section('title', 'Reset your password')
@section('preheader', 'Your 6-digit Novastaq Store password reset code is '.$otp.'. Valid for '.$minutes.' minutes.')
@section('reason', 'You received this email because a password reset was requested for your account on store.novastaq.com.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Account Security',
        'title' => 'Reset your password',
        'intro' => 'Hi '.(\Illuminate\Support\Str::before($user->name, ' ') ?: 'there').', use the 6-digit code below to choose a new password for your Novastaq Store account.',
    ])

    <div style="margin:24px 0;padding:24px;background:#f4f4f5;border-radius:14px;text-align:center;">
        <span style="font-family:monospace,'SFMono-Regular',Consolas,Courier,monospace;font-size:36px;font-weight:700;letter-spacing:0.25em;color:#0b0b0f;">{{ $otp }}</span>
        <p style="margin:10px 0 0;font-size:12px;color:#71717a;">This code will expire in {{ $minutes }} minutes.</p>
    </div>

    <p style="margin:16px 0 0;font-size:13px;line-height:1.5;color:#71717a;">If you didn't request a password reset, you can safely ignore this email. Your account remains secure.</p>
@endsection
