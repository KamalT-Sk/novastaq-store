@extends('emails.layout')
@section('title', 'New order')
@section('preheader', $order->customer_name.' paid '.\App\Support\Money::naira($order->total).' for '.$order->number.'.')
@section('reason', 'Internal notification for the Novastaq Store team.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => $order->isService() ? 'Plan to activate' : 'New order · arrange delivery',
        'title' => $order->number.' · '.\App\Support\Money::naira($order->total),
        'intro' => 'Paid '.e($order->paid_at?->format('j M Y, g:ia')).' by <strong style="color:#0b0b0f;">'.e($order->customer_name).'</strong>.',
    ])

    @include('emails.partials.items')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 0;border:1px solid #ececef;border-radius:14px;">
        <tr><td style="padding:16px 18px;font-size:14px;line-height:1.7;color:#3f3f46;">
            <span style="font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#a1a1aa;">Customer</span><br>
            <strong style="color:#0b0b0f;">{{ $order->customer_name }}</strong><br>
            <a href="mailto:{{ $order->email }}" style="color:#4f46e5;">{{ $order->email }}</a><br>
            {{ $order->phone }} · <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/\D/', '', $order->phone)) }}" style="color:#4f46e5;">WhatsApp them</a>
            @if ($order->address)<br>{{ $order->address }}, {{ $order->city }}, {{ $order->state }}@endif
            @if ($order->notes)<br><em style="color:#71717a;">“{{ $order->notes }}”</em>@endif
        </td></tr>
    </table>

    @include('emails.partials.button', ['url' => route('admin.orders.show', $order), 'label' => $order->isService() ? 'Activate in admin' : 'Open in admin'])
@endsection
