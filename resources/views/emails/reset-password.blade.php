@extends('emails.layout')
@section('title', 'Reset your password')
@section('preheader', 'Use this link to choose a new password. It expires in '.$minutes.' minutes.')
@section('reason', 'You received this email because a password reset was requested for your account on store.novastaq.com.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Account security',
        'title' => 'Reset your password',
        'intro' => 'We got a request to reset the password for your Novastaq Store account. Tap the button below to choose a new one.',
    ])

    @include('emails.partials.button', ['url' => $url, 'label' => 'Choose a new password'])

    <p style="margin:20px 0 0;font-size:13px;color:#71717a;">This link expires in {{ $minutes }} minutes. If you didn't ask for this, you can ignore this email. Your password won't change.</p>
    <p style="margin:12px 0 0;font-size:12px;color:#a1a1aa;word-break:break-all;">Button not working? Paste this into your browser:<br><a href="{{ $url }}" style="color:#71717a;">{{ $url }}</a></p>
@endsection
