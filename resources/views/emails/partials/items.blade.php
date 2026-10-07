<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e4e4e7;border-bottom:1px solid #e4e4e7;margin:20px 0;font-size:14px;">
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:10px 0;">
                {{ $item->name }} × {{ $item->quantity }}
                @if ($item->account_details)<br><span style="color:#71717a;">For: <strong style="color:#0b0b0f;">{{ $item->account_details }}</strong></span>@endif
            </td>
            <td align="right" style="padding:10px 0;white-space:nowrap;">{{ \App\Support\Money::naira($item->line_total) }}</td>
        </tr>
    @endforeach
    @if ($order->delivery_fee)
        <tr><td style="padding:10px 0;color:#71717a;">Delivery</td><td align="right" style="padding:10px 0;color:#71717a;">{{ \App\Support\Money::naira($order->delivery_fee) }}</td></tr>
    @endif
    <tr><td style="padding:12px 0;font-weight:600;font-size:16px;">Total</td><td align="right" style="padding:12px 0;font-weight:600;font-size:16px;">{{ \App\Support\Money::naira($order->total) }}</td></tr>
</table>
