@extends('emails.layout')
@section('title', 'You\'ve been added to Novastaq Store')
@section('preheader', $by->name.' gave you '.$user->roleLabel().' access to the Novastaq Store admin.')
@section('reason', 'You received this email because '.$by->name.' added you to the Novastaq Store team.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Team access',
        'title' => 'Hi '.\Illuminate\Support\Str::before($user->name, ' ').', you\'re on the team.',
        'intro' => e($by->name).' added you to the Novastaq Store admin.',
    ])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 4px;background:#eef2ff;border-radius:14px;">
        <tr><td style="padding:18px 20px;">
            <p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Your role</p>
            <p style="margin:0;font-size:17px;font-weight:600;color:#1e1b4b;">{{ $user->roleLabel() }}</p>
            <p style="margin:6px 0 0;font-size:14px;line-height:1.5;color:#4338ca;">{{ \App\Models\User::ROLES[$user->role]['about'] ?? '' }}</p>
        </td></tr>
    </table>

    @if ($setPassword)
        <p style="margin:20px 0 0;color:#52525b;">First, choose a password. Your sign-in email is <strong style="color:#0b0b0f;">{{ $user->email }}</strong>.</p>
        @include('emails.partials.button', ['url' => $url, 'label' => 'Set my password'])
        <p style="margin:16px 0 0;font-size:13px;color:#71717a;">The link expires in {{ $minutes }} minutes. If it has expired, go to <a href="{{ route('password.request') }}" style="color:#4f46e5;">Forgot password</a> and enter your email to get a new one. After that, sign in and open <strong>Admin</strong> at the top of the store.</p>
    @else
        <p style="margin:20px 0 0;color:#52525b;">Sign in with your usual account (<strong style="color:#0b0b0f;">{{ $user->email }}</strong>) and you'll see <strong style="color:#0b0b0f;">Admin</strong> at the top of the store.</p>
        @include('emails.partials.button', ['url' => $url, 'label' => 'Open the admin'])
    @endif
@endsection
