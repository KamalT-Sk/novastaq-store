@extends('emails.layout')
@section('title', 'Your order is on its way')
@section('preheader', 'Order '.$order->number.' has left us and is heading to '.$order->city.'.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'On its way',
        'title' => 'Your order is on its way.',
        'intro' => 'It\'s heading to '.e($order->city).', '.e($order->state).'. Our delivery partner will call <strong style="color:#0b0b0f;">'.e($order->phone).'</strong> when they arrive.',
    ])
    @include('emails.partials.progress', ['steps' => ['Ordered', 'Paid', 'On the way', 'Delivered'], 'current' => 2])

    @include('emails.partials.items')

    <p style="margin:8px 0 0;font-size:14px;color:#52525b;">Please check your items when they arrive and tell us within 48 hours if anything is wrong. See our <a href="{{ rtrim(config('store.main_site'), '/') }}/returns" style="color:#4f46e5;">returns policy</a>.</p>

    @if ($order->user_id)
        @include('emails.partials.button', ['url' => route('dashboard'), 'label' => 'View my orders'])
    @endif
@endsection
