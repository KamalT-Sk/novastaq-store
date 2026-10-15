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

    @php($links = $item->digitalLinks())
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;">
        <tr><td style="padding:18px 20px;">
            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#15803d;">
                {{ count($links) > 1 ? 'Your Personal Access Links ('.count($links).')' : 'Your Personal Access Link' }}
            </p>
            @foreach ($links as $idx => $link)
                <div style="margin:8px 0;padding:10px 12px;background:#ffffff;border:1px solid #bbf7d0;border-radius:8px;">
                    @if (count($links) > 1)
                        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;">Link #{{ $idx + 1 }}</p>
                    @endif
                    <p style="margin:0;font-size:14px;font-weight:600;color:#14532d;font-family:'SFMono-Regular',Menlo,Consolas,monospace;word-break:break-all;">
                        <a href="{{ $link }}" style="color:#15803d;text-decoration:underline;">{{ $link }}</a>
                    </p>
                </div>
            @endforeach
            @if ($item->digital_instructions)
                <p style="margin:12px 0 0;font-size:13px;color:#166534;line-height:1.5;">{{ $item->digital_instructions }}</p>
            @endif
            <p style="margin:10px 0 0;font-size:12px;color:#86efac;">Order {{ $order->number }} · {{ \App\Support\Money::naira($order->total) }}</p>
        </td></tr>
    </table>

    @if (count($links) === 1 && filter_var($links[0], FILTER_VALIDATE_URL))
        @include('emails.partials.button', ['url' => $links[0], 'label' => 'Open your link now ↗'])
    @else
        @include('emails.partials.button', ['url' => route('dashboard'), 'label' => 'View all links in my account'])
    @endif
@endsection

