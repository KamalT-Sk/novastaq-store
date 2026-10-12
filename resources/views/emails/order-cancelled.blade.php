@extends('emails.layout')
@section('title', 'Order cancelled')
@section('preheader', 'Your order '.$order->number.' has been cancelled.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Order cancelled',
        'title' => 'Order '.$order->number.' has been cancelled.',
        'intro' => 'Your order has been cancelled. If you believe this is a mistake or have questions, please reply directly to this email or contact support.',
    ])

    @include('emails.partials.items')

    <p style="margin:8px 0 0;font-size:14px;color:#52525b;">Need help? Reply directly to this email or reach us at <a href="mailto:{{ config('store.support_email') }}" style="color:#4f46e5;">{{ config('store.support_email') }}</a>.</p>

    @include('emails.partials.button', ['url' => route('shop'), 'label' => 'Continue shopping'])
@endsection
