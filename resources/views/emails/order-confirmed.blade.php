@extends('emails.layout')
@section('title', 'Order confirmed')
@section('preheader', 'We\'ve received your payment for order '.$order->number.'. Here\'s your receipt.')
@section('body')
    @php($first = \Illuminate\Support\Str::before($order->customer_name, ' '))
    @if ($order->isService())
        @include('emails.partials.heading', [
            'eyebrow' => 'Payment received',
            'title' => "Thank you, {$first}.",
            'intro' => 'Your order is confirmed and we\'re activating your plan now. We\'ll email you the moment it\'s ready, usually within a few hours.',
        ])
        @include('emails.partials.progress', ['steps' => ['Paid', 'Activating', 'Active'], 'current' => 0])
    @elseif ($order->isDigital())
        @if ($order->pendingDigitalItems()->isEmpty())
            @include('emails.partials.heading', [
                'eyebrow' => 'Payment received · Access ready',
                'title' => "Thank you, {$first}.",
                'intro' => 'Your digital order is confirmed! Your access link is ready and listed below.',
            ])
            @include('emails.partials.progress', ['steps' => ['Paid', 'Delivered'], 'current' => 1])
        @else
            @include('emails.partials.heading', [
                'eyebrow' => 'Payment received · Preparing access',
                'title' => "Thank you, {$first}.",
                'intro' => 'Your order is confirmed. We\'re preparing your personal access link and will email it to you shortly.',
            ])
            @include('emails.partials.progress', ['steps' => ['Paid', 'Preparing', 'Delivered'], 'current' => 1])
        @endif
    @else
        @include('emails.partials.heading', [
            'eyebrow' => 'Payment received',
            'title' => "Thank you, {$first}.",
            'intro' => 'Your order is confirmed. We\'ll call or WhatsApp you on <strong style="color:#0b0b0f;">'.e($order->phone).'</strong> to arrange delivery to '.e($order->city).', '.e($order->state).'.',
        ])
        @include('emails.partials.progress', ['steps' => ['Ordered', 'Paid', 'On the way', 'Delivered'], 'current' => 1])
    @endif

    @if ($order->items->contains(fn ($i) => ! empty($i->digitalLinks()) || ! empty($i->digital_file)))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;">
            <tr><td style="padding:18px 20px;">
                <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#15803d;">⚡ Your Personal Access</p>
                @foreach ($order->items as $i)
                    @php($links = $i->digitalLinks())
                    @if (! empty($links) || ! empty($i->digital_file))
                        <div style="margin-bottom:14px;">
                            <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#14532d;">{{ $i->name }} @if ($i->quantity > 1) <span style="font-size:12px;color:#15803d;font-weight:normal;">(Quantity: {{ $i->quantity }})</span>@endif</p>
                            @if ($i->digital_instructions)
                                <p style="margin:2px 0 8px;font-size:12px;color:#166534;">{{ $i->digital_instructions }}</p>
                            @endif
                            @foreach ($links as $idx => $link)
                                <div style="margin:6px 0;padding:10px 12px;background:#ffffff;border:1px solid #bbf7d0;border-radius:8px;">
                                    @if (count($links) > 1)
                                        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;">Link #{{ $idx + 1 }}</p>
                                    @endif
                                    <p style="margin:0;font-size:13px;color:#166534;font-family:'SFMono-Regular',Menlo,Consolas,monospace;word-break:break-all;">
                                        <a href="{{ $link }}" style="color:#15803d;text-decoration:underline;">{{ $link }}</a>
                                    </p>
                                </div>
                            @endforeach
                            @if ($i->digital_file)
                                <div style="margin:8px 0;padding:10px 12px;background:#ffffff;border:1px solid #bbf7d0;border-radius:8px;">
                                    <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;">Download File</p>
                                    <a href="{{ $i->downloadUrl() }}" style="display:inline-block;padding:8px 14px;background:#15803d;color:#ffffff;text-decoration:none;border-radius:6px;font-size:13px;font-weight:600;">Download {{ $i->name }} (PDF / File) ↗</a>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </td></tr>
        </table>
    @endif

    @include('emails.partials.items')

    @if (! $order->isService() && $order->address)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 0;">
            <tr><td style="font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#a1a1aa;padding-bottom:4px;">Delivering to</td></tr>
            <tr><td style="font-size:14px;line-height:1.55;color:#3f3f46;">{{ $order->customer_name }}<br>{{ $order->address }}<br>{{ $order->city }}, {{ $order->state }}</td></tr>
        </table>
    @endif

    @if ($order->user_id)
        @include('emails.partials.button', ['url' => route('dashboard'), 'label' => 'View my orders'])
    @else
        @include('emails.partials.button', ['url' => route('register'), 'label' => 'Create an account to track orders'])
    @endif
@endsection
