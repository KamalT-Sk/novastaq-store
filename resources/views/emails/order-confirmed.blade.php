@extends('emails.layout')
@section('body')
    <p style="margin:0 0 4px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Payment received</p>
    <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;">Thank you, {{ \Illuminate\Support\Str::before($order->customer_name, ' ') }}.</h1>
    @if ($order->isService())
        <p style="margin:0;color:#52525b;">Your order <strong style="color:#0b0b0f;">{{ $order->number }}</strong> is confirmed. We're activating your plan now and will email you as soon as it's ready.</p>
    @else
        <p style="margin:0;color:#52525b;">Your order <strong style="color:#0b0b0f;">{{ $order->number }}</strong> is confirmed. We'll call or WhatsApp you on {{ $order->phone }} to arrange delivery to {{ $order->city }}, {{ $order->state }}.</p>
    @endif

    @include('emails.partials.items')

    <p style="margin:0;color:#52525b;">Questions? Just reply to this email or <a href="{{ config('store.whatsapp_url') }}" style="color:#4f46e5;">chat with us on WhatsApp</a>. Keep your order number handy.</p>
@endsection
