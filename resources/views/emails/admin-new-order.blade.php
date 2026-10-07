@extends('emails.layout')
@section('body')
    <p style="margin:0 0 4px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">{{ $order->isService() ? 'Plan to activate' : 'New order' }}</p>
    <h1 style="margin:0 0 8px;font-size:24px;line-height:1.25;">{{ $order->number }} · {{ \App\Support\Money::naira($order->total) }}</h1>
    <p style="margin:0;color:#52525b;">Paid {{ $order->paid_at?->format('j M Y, g:ia') }} by <strong style="color:#0b0b0f;">{{ $order->customer_name }}</strong>.</p>

    @include('emails.partials.items')

    <p style="margin:0 0 4px;"><strong>Customer</strong></p>
    <p style="margin:0 0 16px;color:#52525b;">
        {{ $order->customer_name }}<br>
        {{ $order->email }}<br>
        {{ $order->phone }} · <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/\D/', '', $order->phone)) }}" style="color:#4f46e5;">WhatsApp them</a>
        @if ($order->address)<br>{{ $order->address }}, {{ $order->city }}, {{ $order->state }}@endif
    </p>
    @if ($order->notes)<p style="margin:0 0 16px;color:#52525b;font-style:italic;">“{{ $order->notes }}”</p>@endif

    <a href="{{ route('admin.orders.show', $order) }}" style="display:inline-block;background:#0b0b0f;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-size:14px;font-weight:500;">Open in admin</a>
@endsection
