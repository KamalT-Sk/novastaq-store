@extends('emails.layout')
@section('body')
    <p style="margin:0 0 4px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">On its way</p>
    <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;">Your order is on its way.</h1>
    <p style="margin:0;color:#52525b;">Order <strong style="color:#0b0b0f;">{{ $order->number }}</strong> has left us and is heading to {{ $order->city }}, {{ $order->state }}. Our delivery partner will call {{ $order->phone }} on arrival.</p>

    @include('emails.partials.items')

    <p style="margin:0;color:#52525b;">Please check your items when they arrive and tell us within 48 hours if anything is wrong. Reply here or <a href="{{ config('store.whatsapp_url') }}" style="color:#4f46e5;">WhatsApp us</a>.</p>
@endsection
