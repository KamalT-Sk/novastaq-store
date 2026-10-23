<x-app-layout title="Track Order">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 pt-10 pb-20">
        {{-- Header --}}
        <div class="text-center max-w-lg mx-auto mb-10">
            <p class="eyebrow">Self-Service Lookup</p>
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-[-0.03em] mb-3">Track your order</h1>
            <p class="text-[15px] text-zinc-500">
                Enter your order number and the email or phone number you used during checkout to view live status.
            </p>
        </div>

        {{-- Tracking Form --}}
        <div class="panel p-6 sm:p-8 mb-10 shadow-xs">
            <form action="{{ route('orders.track.lookup') }}" method="POST" class="grid sm:grid-cols-[1.2fr_1.4fr_auto] gap-3.5 items-end">
                @csrf
                <div>
                    <label for="track-number" class="label mb-1.5 block">Order Number</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400 font-mono text-sm">#</span>
                        <input type="text"
                               id="track-number"
                               name="number"
                               value="{{ old('number', $number) }}"
                               required
                               placeholder="NS-10023"
                               class="field h-11 pl-8 text-sm font-mono uppercase tracking-wider"
                               autocomplete="off">
                    </div>
                </div>

                <div>
                    <label for="track-contact" class="label mb-1.5 block">Email or Phone</label>
                    <input type="text"
                           id="track-contact"
                           name="contact"
                           value="{{ old('contact', $contact) }}"
                           required
                           placeholder="you@email.com or 0815..."
                           class="field h-11 text-sm">
                </div>

                <div>
                    <button type="submit" class="btn-primary h-11 px-6 w-full justify-center text-sm font-semibold whitespace-nowrap">
                        Track Order
                    </button>
                </div>
            </form>
        </div>

        {{-- Order Result Card --}}
        @if ($order)
            @php
                $isPaid = $order->isPaid();
                $isCancelled = $order->status === 'cancelled';
                $isDelivered = in_array($order->status, ['delivered', 'completed'], true);
                $isShipped = $order->status === 'shipped';
                $isProcessing = in_array($order->status, ['processing', 'active'], true) || ($isPaid && ! $isShipped && ! $isDelivered);

                $waMsg = "Hello Novastaq! I have a question regarding my order #{$order->number} placed on {$order->created_at->format('M j, Y')}.";
                $waUrl = rtrim((string) config('store.whatsapp_url'), '/') . (str_contains(config('store.whatsapp_url'), '?') ? '&' : '?') . 'text=' . rawurlencode($waMsg);
            @endphp

            <div class="panel p-6 sm:p-8 space-y-8 animate-fadeIn border-ink/15 shadow-sm">
                {{-- Top Summary Bar --}}
                <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-black/[0.06]">
                    <div>
                        <div class="flex items-center gap-3 mb-1.5">
                            <h2 class="text-2xl font-bold font-mono tracking-tight text-ink">#{{ $order->number }}</h2>
                            @if ($isCancelled)
                                <span class="badge !bg-red-50 !text-red-700 !border-red-200">Cancelled</span>
                            @elseif ($isDelivered)
                                <span class="badge !bg-emerald-50 !text-emerald-700 !border-emerald-200">Delivered / Ready</span>
                            @elseif ($isShipped)
                                <span class="badge !bg-blue-50 !text-blue-700 !border-blue-200">Shipped</span>
                            @elseif ($isPaid)
                                <span class="badge !bg-emerald-50 !text-emerald-700 !border-emerald-200">Paid & Processing</span>
                            @else
                                <span class="badge !bg-amber-50 !text-amber-800 !border-amber-200">Payment Pending</span>
                            @endif
                        </div>
                        <p class="text-sm text-zinc-500">
                            Placed on {{ $order->created_at->format('F j, Y · g:ia') }}
                            @if ($order->customer_name) · {{ $order->customer_name }} @endif
                        </p>
                    </div>

                    <div class="text-right">
                        <span class="text-xs uppercase tracking-wider text-zinc-400 font-semibold block mb-0.5">Total Paid</span>
                        <span class="text-2xl font-bold text-ink">@naira($order->total)</span>
                    </div>
                </div>

                {{-- Visual Progress Stepper --}}
                @if (! $isCancelled)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-4">Live Fulfillment Progress</h3>
                        <div class="grid grid-cols-3 gap-2 sm:gap-4 relative">
                            {{-- Step 1: Placed & Paid --}}
                            <div class="flex flex-col items-center text-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $isPaid ? 'bg-emerald-600 text-white shadow-xs' : 'bg-zinc-200 text-zinc-500' }}">
                                    @if ($isPaid) ✓ @else 1 @endif
                                </div>
                                <span class="text-xs font-semibold text-ink">Order Paid</span>
                                <span class="text-[11px] text-zinc-400 mt-0.5">{{ $order->paid_at ? $order->paid_at->format('M j, g:ia') : 'Awaiting payment' }}</span>
                            </div>

                            {{-- Step 2: Processing / Activating --}}
                            <div class="flex flex-col items-center text-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $isShipped || $isDelivered ? 'bg-emerald-600 text-white shadow-xs' : ($isPaid ? 'bg-blue-600 text-white animate-pulse' : 'bg-zinc-100 text-zinc-400 border border-zinc-200') }}">
                                    @if ($isShipped || $isDelivered) ✓ @else 2 @endif
                                </div>
                                <span class="text-xs font-semibold text-ink">
                                    {{ $order->isDirect() ? 'Plan Activation' : ($order->hasDigital() ? 'File Preparation' : 'Order Packing') }}
                                </span>
                                <span class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ $isShipped || $isDelivered ? 'Completed' : ($isPaid ? 'In Progress' : 'Queued') }}
                                </span>
                            </div>

                            {{-- Step 3: Shipped / Delivered / Ready --}}
                            <div class="flex flex-col items-center text-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $isDelivered ? 'bg-emerald-600 text-white shadow-xs' : ($isShipped ? 'bg-blue-600 text-white shadow-xs' : 'bg-zinc-100 text-zinc-400 border border-zinc-200') }}">
                                    @if ($isDelivered) ✓ @else 3 @endif
                                </div>
                                <span class="text-xs font-semibold text-ink">
                                    {{ $order->isDirect() ? 'Account Ready' : ($order->hasDigital() ? 'Instant Delivery' : 'Delivered') }}
                                </span>
                                <span class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ $isDelivered ? 'Delivered' : ($isShipped ? 'In Transit' : 'Final Step') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Items Ordered --}}
                <div class="pt-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">Items in this order</h3>
                    <div class="divide-y divide-black/[0.06] rounded-xl border border-black/[0.06] bg-zinc-50/50 overflow-hidden">
                        @foreach ($order->items as $item)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-12 h-12 rounded-lg bg-white border border-black/[0.06] flex items-center justify-center overflow-hidden shrink-0">
                                        @if ($item->product && $item->product->images->isNotEmpty())
                                            <img src="{{ $item->product->images->first()->url() }}" alt="{{ $item->name }}" class="w-full h-full object-contain p-1">
                                        @else
                                            <svg class="w-6 h-6 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-ink">{{ $item->displayName() }}</p>
                                        <p class="text-xs text-zinc-500">Qty: {{ $item->quantity }} · @naira($item->unit_price) each</p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span class="text-sm font-semibold text-ink">@naira($item->total)</span>
                                    @if ($item->digital_file && $isPaid)
                                        <div class="mt-1">
                                            <a href="{{ $item->downloadUrl() }}" class="inline-flex items-center gap-1 text-xs font-semibold text-accent hover:underline">
                                                <span>Download</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.5V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Delivery Info (if physical) --}}
                @if ($order->address)
                    <div class="rounded-xl p-4 bg-zinc-50 border border-black/[0.06] text-sm space-y-1">
                        <span class="text-xs uppercase tracking-wider text-zinc-400 font-semibold block mb-1">Delivery Destination</span>
                        <p class="font-medium text-ink">{{ $order->customer_name }} · {{ $order->phone }}</p>
                        <p class="text-zinc-600">{{ $order->address }}</p>
                        <p class="text-zinc-600">{{ $order->city }}, {{ $order->state }}</p>
                    </div>
                @endif

                {{-- Unpaid Pay-Now Action --}}
                @if (! $isPaid && ! $isCancelled)
                    <div class="rounded-xl p-4 bg-amber-50 border border-amber-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-amber-900">Payment for this order has not been completed.</p>
                            <p class="text-xs text-amber-700 mt-0.5">Click below to proceed to our secure payment gateway.</p>
                        </div>
                        <a href="{{ route('orders.pay', $order) }}" class="btn-primary h-10 px-5 text-sm font-semibold shrink-0">
                            Pay @naira($order->total) Now
                        </a>
                    </div>
                @endif

                {{-- Bottom Support Actions --}}
                <div class="pt-4 border-t border-black/[0.06] flex flex-wrap items-center justify-between gap-4">
                    <p class="text-xs text-zinc-500">
                        Need help with this order? Our support team is online to assist.
                    </p>
                    <a href="{{ $waUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 h-9 px-4 rounded-full text-xs font-semibold bg-[#25D366] hover:bg-[#20ba5a] text-white shadow-xs transition-colors">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.983z"/></svg>
                        <span>Chat on WhatsApp</span>
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
