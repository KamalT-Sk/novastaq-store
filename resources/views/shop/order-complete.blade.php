<x-app-layout title="Order {{ $order->number }}">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-16">
        <div class="panel p-8 md:p-10">
            @if ($paid)
                <p class="eyebrow">Payment received</p>
                <h1 class="text-3xl font-semibold tracking-[-0.03em] mb-3">Thank you, {{ Str::before($order->customer_name, ' ') }}.</h1>
                @if ($order->isService())
                    <p class="text-zinc-500 mb-8">Your order <span class="font-medium text-ink">{{ $order->number }}</span> is confirmed. We'll activate it for <span class="font-medium text-ink">{{ $order->items->first()->account_details }}</span> and confirm with you on {{ $order->phone }}.</p>
                @elseif ($order->isDigital())
                    @if ($order->pendingDigitalItems()->isEmpty())
                        <p class="text-zinc-500 mb-8">Your order <span class="font-medium text-ink">{{ $order->number }}</span> is confirmed! Your personal link and access details are ready below.</p>
                    @else
                        <p class="text-zinc-500 mb-8">Your order <span class="font-medium text-ink">{{ $order->number }}</span> is confirmed. We are preparing your personal link and will email it to <strong class="text-ink">{{ $order->email }}</strong> shortly.</p>
                    @endif
                @else
                    <p class="text-zinc-500 mb-8">Your order <span class="font-medium text-ink">{{ $order->number }}</span> is confirmed. We'll contact you on {{ $order->phone }} to arrange delivery.</p>
                @endif

                @if ($order->hasDigital())
                    <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6 text-left">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold">✓</span>
                            <h2 class="text-lg font-semibold text-emerald-950">Your Digital Delivery</h2>
                        </div>

                        <div class="space-y-4">
                            @foreach ($order->items as $item)
                                @if ($item->isDigital())
                                    <div class="rounded-xl border border-emerald-200/80 bg-white p-4 shadow-xs">
                                        <p class="font-medium text-ink text-[15px] mb-1">{{ $item->name }}</p>
                                        @if ($item->digital_instructions)
                                            <p class="text-[13px] text-zinc-600 mb-3">{{ $item->digital_instructions }}</p>
                                        @endif

                                        @if ($item->digital_content)
                                            <div x-data="{ copied: false }" class="space-y-2">
                                                <label class="text-[12px] font-semibold text-zinc-500 uppercase tracking-wider block">Access Link / Key</label>
                                                <div class="flex flex-col sm:flex-row gap-2">
                                                    <input type="text" readonly value="{{ $item->digital_content }}" class="field h-10 font-mono text-[13px] bg-zinc-50 select-all flex-1">
                                                    <button type="button" @click="navigator.clipboard.writeText('{{ addslashes($item->digital_content) }}'); copied = true; setTimeout(() => copied = false, 2500)" class="btn-secondary h-10 shrink-0">
                                                        <span x-show="!copied">Copy link</span>
                                                        <span x-show="copied" x-cloak class="text-emerald-700 font-semibold">✓ Copied!</span>
                                                    </button>
                                                    @if (filter_var($item->digital_content, FILTER_VALIDATE_URL))
                                                        <a href="{{ $item->digital_content }}" target="_blank" rel="noopener" class="btn-primary h-10 shrink-0">Open link ↗</a>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif ($item->digital_file)
                                            <div class="mt-2">
                                                <a href="{{ route('orders.download', ['order' => $order->number, 'item' => $item->id]) }}" class="btn-primary inline-flex items-center gap-2">
                                                    <span>Download PDF / File</span>
                                                    <span>↓</span>
                                                </a>
                                            </div>
                                        @else
                                            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-[13px] text-amber-800">
                                                ⏳ <strong>Link being prepared:</strong> Our team is assigning your unique personal link and will email it to <strong>{{ $order->email }}</strong> shortly.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <p class="eyebrow">Payment not confirmed yet</p>
                <h1 class="text-3xl font-semibold tracking-[-0.03em] mb-3">We're waiting for {{ $order->payment_provider ? ucfirst($order->payment_provider) : 'confirmation' }}.</h1>
                <p class="text-zinc-500 mb-8">If you completed the payment, it will be confirmed within a few minutes and we'll email {{ $order->email }}. If it didn't go through, your cart is still saved and you can <a href="{{ route('checkout') }}" class="underline underline-offset-4 text-ink">try again</a>.</p>
            @endif

            <ul class="divide-y divide-black/[0.06] border-y border-black/[0.06] mb-6">
                @foreach ($order->items as $item)
                    <li class="flex justify-between py-3 text-[14px]"><span>{{ $item->name }} × {{ $item->quantity }}</span><span class="font-medium">@naira($item->line_total)</span></li>
                @endforeach
                @if ($order->delivery_fee)
                    <li class="flex justify-between py-3 text-[14px] text-zinc-500"><span>Delivery</span><span>@naira($order->delivery_fee)</span></li>
                @endif
            </ul>
            <p class="flex justify-between text-lg font-semibold mb-8"><span>Total</span><span>@naira($order->total)</span></p>

            @guest
                <p class="mb-6 rounded-xl bg-zinc-50 border border-black/[0.06] px-4 py-3 text-[14px] text-zinc-600">
                    Want to track your orders? <a href="{{ route('register') }}" class="font-medium text-ink underline underline-offset-4">Create a free account</a> with {{ $order->email }}.
                </p>
            @endguest
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('shop') }}" class="btn-primary">Continue shopping</a>
                <a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="btn-secondary">Questions? Chat with us</a>
            </div>
        </div>
    </div>
</x-app-layout>
