@extends('emails.layout')
@section('title', 'Your digital access is ready')
@section('preheader', 'Your personal link for '.$item->name.' is ready for order '.$order->number.'.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Delivery complete',
        'title' => 'Your '.$item->name.' is ready.',
        'intro' => 'Here is your personal access link for order '.$order->number.'.',
    ])
    @include('emails.partials.progress', ['steps' => ['Paid', 'Preparing', 'Delivered'], 'current' => 2])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;">
        <tr><td style="padding:18px 20px;">
            <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#15803d;">Your Access Link</p>
            <p style="margin:0;font-size:15px;font-weight:600;color:#14532d;font-family:'SFMono-Regular',Menlo,Consolas,monospace;word-break:break-all;">
                <a href="{{ $item->digital_content }}" style="color:#15803d;text-decoration:underline;">{{ $item->digital_content }}</a>
            </p>
            @if ($item->digital_instructions)
                <p style="margin:12px 0 0;font-size:13px;color:#166534;line-height:1.5;">{{ $item->digital_instructions }}</p>
            @endif
            <p style="margin:10px 0 0;font-size:12px;color:#86efac;">Order {{ $order->number }} · {{ \App\Support\Money::naira($order->total) }}</p>
        </td></tr>
    </table>

    @if (filter_var($item->digital_content, FILTER_VALIDATE_URL))
        @include('emails.partials.button', ['url' => $item->digital_content, 'label' => 'Open your link now ↗'])
    @else
        @include('emails.partials.button', ['url' => route('dashboard'), 'label' => 'View in my account'])
    @endif
@endsection

