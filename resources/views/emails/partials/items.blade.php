{{-- Order summary card: items (with photo when there is one), delivery, total --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;border:1px solid #ececef;border-radius:14px;">
    <tr><td style="padding:14px 18px;border-bottom:1px solid #f0f0f2;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#a1a1aa;">
        Order {{ $order->number }}<span style="float:right;letter-spacing:0;text-transform:none;font-weight:500;">{{ ($order->paid_at ?? $order->created_at)->format('j M Y') }}</span>
    </td></tr>
    @foreach ($order->items as $item)
        @php($photo = $item->product?->coverUrl())
        <tr><td style="padding:14px 18px;border-bottom:1px solid #f0f0f2;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                <td width="56" valign="top" style="padding-right:14px;">
                    @if ($photo)
                        <img src="{{ $photo }}" width="56" height="56" alt="" style="display:block;width:56px;height:56px;border-radius:10px;border:1px solid #ececef;object-fit:contain;background:#fafafa;">
                    @else
                        <div style="width:56px;height:56px;line-height:56px;border-radius:10px;background:#f4f4f5;text-align:center;font-size:22px;">{{ $item->account_details ? '✦' : '📦' }}</div>
                    @endif
                </td>
                <td valign="top" style="font-size:15px;line-height:1.45;color:#0b0b0f;">
                    <strong>{{ $item->name }}</strong>
                    <span style="color:#71717a;font-size:14px;"> × {{ $item->quantity }}</span>
                    @if ($item->account_details)
                        <br><span style="font-size:13px;color:#71717a;">For</span>
                        <span style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:6px;background:#eef2ff;color:#3730a3;font-size:13px;font-family:'SFMono-Regular',Menlo,Consolas,monospace;">{{ $item->account_details }}</span>
                    @endif
                </td>
                <td valign="top" align="right" style="font-size:15px;color:#0b0b0f;white-space:nowrap;">{{ \App\Support\Money::naira($item->line_total) }}</td>
            </tr></table>
        </td></tr>
    @endforeach
    <tr><td style="padding:14px 18px 6px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#52525b;">
            @if ($order->discount_amount > 0 || $order->delivery_fee)
                <tr><td style="padding:3px 0;">Subtotal</td><td align="right" style="padding:3px 0;">{{ \App\Support\Money::naira($order->subtotal) }}</td></tr>
            @endif
            @if ($order->discount_amount > 0)
                <tr><td style="padding:3px 0;color:#15803d;font-weight:600;">Discount ({{ $order->discount_code }})</td><td align="right" style="padding:3px 0;color:#15803d;font-weight:600;">-{{ \App\Support\Money::naira($order->discount_amount) }}</td></tr>
            @endif
            @if ($order->delivery_fee)
                <tr><td style="padding:3px 0;">Delivery</td><td align="right" style="padding:3px 0;">{{ \App\Support\Money::naira($order->delivery_fee) }}</td></tr>
            @endif
            <tr><td style="padding:10px 0 12px;font-size:17px;font-weight:700;color:#0b0b0f;">Total paid</td><td align="right" style="padding:10px 0 12px;font-size:17px;font-weight:700;color:#0b0b0f;">{{ \App\Support\Money::naira($order->total) }}</td></tr>
        </table>
    </td></tr>
</table>
