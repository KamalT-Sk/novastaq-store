@extends('emails.layout')
@php($item = $order->items->first())
@section('title', 'Your plan is active')
@section('preheader', 'Good news: your '.$item?->name.' is active. Sign in to start using it.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'All set',
        'title' => 'Your '.$item?->name.' is active.',
        'intro' => 'Sign in to the account below to start using it. It can take a few minutes to show up.',
    ])
    @include('emails.partials.progress', ['steps' => ['Paid', 'Activating', 'Active'], 'current' => 2])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 4px;background:#eef2ff;border-radius:14px;">
        <tr><td style="padding:18px 20px;">
            <p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Activated for</p>
            <p style="margin:0;font-size:17px;font-weight:600;color:#1e1b4b;font-family:'SFMono-Regular',Menlo,Consolas,monospace;word-break:break-all;">{{ $item?->account_details }}</p>
            <p style="margin:8px 0 0;font-size:13px;color:#4338ca;">Order {{ $order->number }} · {{ \App\Support\Money::naira($order->total) }}</p>
        </td></tr>
    </table>

    @include('emails.partials.button', ['url' => route('shop'), 'label' => 'Explore more plans'])
@endsection
