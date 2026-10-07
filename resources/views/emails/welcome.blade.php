@extends('emails.layout')
@section('body')
    <p style="margin:0 0 4px;font-size:12px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#4f46e5;">Welcome</p>
    <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;">Hi {{ \Illuminate\Support\Str::before($user->name, ' ') }}, your account is ready.</h1>
    <p style="margin:0 0 20px;color:#52525b;">You can now track your orders and plans in one place. Shop genuine phones, laptops and gadgets delivered to your door, or get premium plans like X and ChatGPT activated for you.</p>
    <a href="{{ route('shop') }}" style="display:inline-block;background:#0b0b0f;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-size:14px;font-weight:500;">Start shopping</a>
    <p style="margin:24px 0 0;color:#52525b;">Need help choosing? Reply to this email or <a href="{{ config('store.whatsapp_url') }}" style="color:#4f46e5;">chat with us on WhatsApp</a>.</p>
@endsection
