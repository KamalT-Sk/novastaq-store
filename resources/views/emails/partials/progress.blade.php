{{-- Order progress tracker. Vars: $steps (list of labels), $current (index of the latest done step) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;">
    <tr>
        @foreach ($steps as $i => $label)
            @php($done = $i <= $current)
            <td align="center" valign="top" width="{{ floor(100 / count($steps)) }}%" style="font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td valign="middle" style="font-size:0;line-height:0;"><div style="height:3px;font-size:0;line-height:0;background:{{ $i === 0 ? 'transparent' : ($done ? '#4f46e5' : '#e4e4e7') }};">&nbsp;</div></td>
                    <td width="26" align="center">
                        <div style="width:26px;height:26px;line-height:26px;border-radius:13px;font-size:13px;font-weight:700;text-align:center;{{ $done ? 'background:#4f46e5;color:#ffffff;' : 'background:#ffffff;color:#a1a1aa;border:2px solid #e4e4e7;line-height:22px;width:22px;height:22px;' }}">{!! $done ? '&#10003;' : ($i + 1) !!}</div>
                    </td>
                    <td valign="middle" style="font-size:0;line-height:0;"><div style="height:3px;font-size:0;line-height:0;background:{{ $i === count($steps) - 1 ? 'transparent' : ($i < $current ? '#4f46e5' : '#e4e4e7') }};">&nbsp;</div></td>
                </tr></table>
                <p style="margin:8px 0 0;font-size:12px;line-height:1.3;font-weight:{{ $i === $current ? '700' : '500' }};color:{{ $done ? '#0b0b0f' : '#a1a1aa' }};">{{ $label }}</p>
            </td>
        @endforeach
    </tr>
</table>
