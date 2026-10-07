@extends('emails.layout')
@section('body')
    @php($item = $order->items->first())
    <p style="margin:0 0 4px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">All set</p>
    <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;">Your {{ $item?->name }} is active.</h1>
    <p style="margin:0 0 20px;color:#52525b;">We've activated it for <strong style="color:#0b0b0f;">{{ $item?->account_details }}</strong>. Sign in to that account to start using it. It can take a few minutes to show up.</p>
    <p style="margin:0;color:#52525b;">Something not right? Reply to this email or <a href="{{ config('store.whatsapp_url') }}" style="color:#4f46e5;">WhatsApp us</a> with your order number <strong style="color:#0b0b0f;">{{ $order->number }}</strong>.</p>
@endsection
