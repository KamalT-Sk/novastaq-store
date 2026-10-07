@extends('emails.layout')
@section('title', 'Welcome to Novastaq Store')
@section('preheader', 'Your account is ready. Genuine gadgets, premium plans and order tracking in one place.')
@section('reason', 'You received this email because you created an account on store.novastaq.com.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Welcome',
        'title' => 'Hi '.\Illuminate\Support\Str::before($user->name, ' ').', your account is ready.',
        'intro' => 'Thanks for joining Novastaq Store. Here\'s what you can do:',
    ])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 4px;">
        @foreach ([
            ['📱', 'Genuine gadgets', 'Phones, laptops, accessories and office tech, delivered across Nigeria.'],
            ['✦', 'Premium plans, activated for you', 'X Premium, ChatGPT, Gemini and more. Pay once, and we set it up on your account.'],
            ['📦', 'Track everything', 'See every order and plan in your account, with email updates at each step.'],
        ] as [$icon, $title, $text])
            <tr><td style="padding:8px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td width="44" valign="top"><div style="width:36px;height:36px;line-height:36px;border-radius:10px;background:#eef2ff;color:#4f46e5;text-align:center;font-size:17px;">{{ $icon }}</div></td>
                    <td valign="top" style="font-size:14px;line-height:1.5;color:#52525b;"><strong style="color:#0b0b0f;font-size:15px;">{{ $title }}</strong><br>{{ $text }}</td>
                </tr></table>
            </td></tr>
        @endforeach
    </table>

    @include('emails.partials.button', ['url' => route('shop'), 'label' => 'Start shopping'])
@endsection
