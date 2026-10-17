@extends('emails.layout')
@php($first = \Illuminate\Support\Str::before($order->customer_name, ' '))
@php($itemSummary = $order->items->first()?->name.(count($order->items) > 1 ? ' and '.(count($order->items) - 1).' other item(s)' : ''))
@section('title', 'Complete your order for '.$itemSummary)
@section('preheader', 'Your order '.$order->number.' is saved. Complete payment to get your access details.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Order recovery · We saved your items',
        'title' => 'Did something go wrong, '.$first.'?',
        'intro' => 'We noticed you didn\'t finish checking out for your order <strong style="color:#0b0b0f;">'.$order->number.'</strong>. Don\'t worry, we\'ve kept your items reserved for you.',
    ])

    @include('emails.partials.items')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;">
        <tr><td style="padding:18px 20px;">
            <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#475569;">⚡ Instant Activation Ready</p>
            <p style="margin:0;font-size:14px;color:#334155;line-height:1.55;">
                As soon as your payment is confirmed, you'll instantly receive your personal access link, download, and receipt on-screen and by email.
            </p>
        </td></tr>
    </table>

    @include('emails.partials.button', [
        'url' => \Illuminate\Support\Facades\URL::temporarySignedRoute('orders.pay', now()->addDays(7), ['order' => $order->number]),
        'label' => 'Complete Payment Now ('.\App\Support\Money::naira($order->total).') ↗',
    ])

    <p style="margin:24px 0 0;font-size:13px;color:#71717a;text-align:center;line-height:1.6;">
        Prefer paying via direct bank transfer or experiencing payment gateway issues?<br>
        <a href="{{ config('store.whatsapp_url') }}" style="color:#4f46e5;font-weight:600;text-decoration:underline;">Chat with us on WhatsApp</a> and our team will assist you immediately.
    </p>
@endsection
