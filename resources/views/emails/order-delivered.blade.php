@extends('emails.layout')
@section('title', 'Your order has been delivered')
@section('preheader', 'Order '.$order->number.($order->city ? ' has been delivered to '.$order->city.'.' : ' has been delivered.'))
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Delivered',
        'title' => 'Your order has been delivered.',
        'intro' => $order->city
            ? 'Your order was successfully delivered to '.e($order->city).', '.e($order->state).'. We hope you love your new device!'
            : 'Your order was successfully delivered. We hope you love your new device!',
    ])
    @include('emails.partials.progress', ['steps' => ['Ordered', 'Paid', 'On the way', 'Delivered'], 'current' => 3])

    @include('emails.partials.items')

    <p style="margin:8px 0 0;font-size:14px;color:#52525b;">Need help or have questions about your order? Reply directly to this email or reach us at <a href="mailto:{{ config('store.support_email') }}" style="color:#4f46e5;">{{ config('store.support_email') }}</a>.</p>

    @if ($order->user_id)
        @include('emails.partials.button', ['url' => route('dashboard'), 'label' => 'View my orders'])
    @endif
@endsection
