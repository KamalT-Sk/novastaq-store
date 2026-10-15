<x-admin-layout title="Automated Store Emails">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">Automated Store Emails</h1>
            <p class="text-[13px] text-zinc-500 mt-0.5">These transactional emails are automatically triggered by customer actions or store events.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge {{ $isConfigured ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' }} border text-xs px-2.5 py-1">
                {{ $driverLabel }}
            </span>
            <a href="{{ route('admin.emails.settings') }}" class="btn-secondary h-9 px-3 text-xs inline-flex items-center gap-1">
                <span>⚙</span>
                <span>Settings</span>
            </a>
        </div>
    </div>

    @include('admin.emails.tabs', ['current' => 'automated'])

    <div class="space-y-4 max-w-4xl">
        {{-- 1. Order Confirmation & Receipt --}}
        <div class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🛒</span>
                    <h3 class="font-semibold text-ink text-[14px]">Order Confirmation &amp; Receipt</h3>
                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px]">Instant</span>
                </div>
                <p class="text-[13px] text-zinc-500">Sent immediately after a payment is verified on Paystack, Tsara, or Nomba.</p>
                <p class="text-[12px] text-zinc-400">Recipients: Customer (receipt) and Admin staff (new order alert).</p>
            </div>
            <div class="text-right flex items-center gap-2">
                <span class="text-xs text-zinc-400 font-mono">emails.order-confirmed</span>
            </div>
        </div>

        {{-- 2. Digital Key & Link Delivery --}}
        <div class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⚡</span>
                    <h3 class="font-semibold text-ink text-[14px]">Digital Content &amp; License Delivery</h3>
                    <span class="badge bg-purple-50 text-purple-700 border border-purple-200 text-[11px]">Instant</span>
                </div>
                <p class="text-[13px] text-zinc-500">Sent when a customer buys digital products (e.g. Gemini AI link, PDF download) or when admin fulfils it.</p>
                <p class="text-[12px] text-zinc-400">Includes direct unclipped copyable link and download instructions.</p>
            </div>
            <div class="text-right flex items-center gap-2">
                <span class="text-xs text-zinc-400 font-mono">emails.digital-delivered</span>
            </div>
        </div>

        {{-- 3. 2-Hour Payment Recovery Reminder --}}
        <div class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⏰</span>
                    <h3 class="font-semibold text-ink text-[14px]">2-Hour Payment Recovery Reminder</h3>
                    <span class="badge bg-sky-50 text-sky-700 border border-sky-200 text-[11px]">Automated / Hourly</span>
                </div>
                <p class="text-[13px] text-zinc-500">Sent to customers who abandoned checkout 2+ hours ago with an unpaid order.</p>
                <p class="text-[12px] text-zinc-400">Includes a 1-click "Complete Payment Now ↗" link that resumes gateway checkout immediately.</p>
            </div>
            <div class="text-right flex items-center gap-2">
                <a href="{{ route('admin.orders.index') }}" class="btn-secondary h-8 px-3 text-xs inline-flex items-center gap-1">
                    <span>Manage Orders →</span>
                </a>
            </div>
        </div>

        {{-- 4. Welcome Email --}}
        <div class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-lg">👋</span>
                    <h3 class="font-semibold text-ink text-[14px]">Customer Welcome Email</h3>
                    <span class="badge bg-zinc-100 text-zinc-700 text-[11px]">On Signup</span>
                </div>
                <p class="text-[13px] text-zinc-500">Sent when a new customer registers an account on the store.</p>
                <p class="text-[12px] text-zinc-400">Introduces the store, customer support WhatsApp, and catalogue.</p>
            </div>
            <div class="text-right flex items-center gap-2">
                <span class="text-xs text-zinc-400 font-mono">emails.welcome</span>
            </div>
        </div>

        {{-- 5. Password Reset OTP --}}
        <div class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🔒</span>
                    <h3 class="font-semibold text-ink text-[14px]">Password Reset Link</h3>
                    <span class="badge bg-zinc-100 text-zinc-700 text-[11px]">On Request</span>
                </div>
                <p class="text-[13px] text-zinc-500">Sent when a user requests a password reset from the login screen.</p>
                <p class="text-[12px] text-zinc-400">Contains a secure expiring token link to reset credentials.</p>
            </div>
            <div class="text-right flex items-center gap-2">
                <span class="text-xs text-zinc-400 font-mono">emails.reset-password</span>
            </div>
        </div>
    </div>
</x-admin-layout>

