@extends('emails.layout')
@section('title', 'Inventory Alert: '.$title)
@section('preheader', 'Inventory alert for '.$product->name.'. Action required in store admin.')
@section('reason', 'Internal inventory alert for the Novastaq Store team.')
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => 'Inventory Alert · Action Needed',
        'title' => $title,
        'intro' => $intro,
    ])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 24px;border:1px solid #ececef;border-radius:14px;background:#fafafa;">
        <tr><td style="padding:16px 18px;font-size:14px;line-height:1.7;color:#3f3f46;">
            <span style="font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#a1a1aa;">Product</span><br>
            <strong style="color:#0b0b0f;font-size:16px;">{{ $product->name }}</strong><br>
            <span style="color:#71717a;">Type: {{ ucfirst($product->type) }} · Category: {{ $product->category?->name ?? 'General' }}</span><br>
            <span style="color:#e11d48;font-weight:600;">Current Stock: {{ $stockStatus }}</span>
        </td></tr>
    </table>

    @include('emails.partials.button', ['url' => $actionUrl, 'label' => 'Restock in Admin'])
@endsection

