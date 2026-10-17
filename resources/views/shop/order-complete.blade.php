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
                                    @php($links = $item->digitalLinks())
                                    <div class="rounded-xl border border-emerald-200/80 bg-white p-5 shadow-xs space-y-3">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-ink text-[16px]">{{ $item->displayName() }}</p>
                                                @if ($item->quantity > 1)
                                                    <span class="inline-block mt-0.5 text-xs text-zinc-500 font-medium">Quantity: {{ $item->quantity }}</span>
                                                @endif
                                            </div>
                                            @if (count($links) > 1)
                                                <div x-data="{ copiedAll: false }">
                                                    <button type="button" @click="navigator.clipboard.writeText({{ json_encode(implode("\n\n", $links)) }}); copiedAll = true; setTimeout(() => copiedAll = false, 2500)" class="btn-secondary h-8 px-3 text-xs inline-flex items-center gap-1.5 shrink-0">
                                                        <span x-show="!copiedAll">Copy all {{ count($links) }} links</span>
                                                        <span x-show="copiedAll" x-cloak class="text-emerald-700 font-bold">✓ All copied!</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($item->digital_instructions)
                                            <p class="text-[13px] text-zinc-600 bg-zinc-50 rounded-lg p-2.5 border border-black/[0.04]">{{ $item->digital_instructions }}</p>
                                        @endif

                                        @if (! empty($links))
                                            <div class="space-y-3 pt-1">
                                                @foreach ($links as $idx => $link)
                                                    <div x-data="{ copied: false }" class="rounded-xl border border-black/[0.08] bg-zinc-50/70 p-3.5 space-y-2.5">
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <span class="text-[11px] font-bold text-zinc-600 uppercase tracking-wider flex items-center gap-1.5">
                                                                @if (count($links) > 1)
                                                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px] flex items-center justify-center font-bold">{{ $idx + 1 }}</span>
                                                                    <span>Personal Link #{{ $idx + 1 }}</span>
                                                                @else
                                                                    <span>🔑 Personal Access Link</span>
                                                                @endif
                                                            </span>
                                                            <div class="flex items-center gap-2">
                                                                <button type="button" @click="navigator.clipboard.writeText({{ Js::from($link) }}); copied = true; setTimeout(() => copied = false, 2500)" class="btn-secondary h-7 px-2.5 text-xs">
                                                                    <span x-show="!copied">Copy link</span>
                                                                    <span x-show="copied" x-cloak class="text-emerald-700 font-bold">✓ Copied!</span>
                                                                </button>
                                                                @if ((filter_var($link, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true)))
                                                                    <a href="{{ $link }}" target="_blank" rel="noopener" class="btn-primary h-7 px-2.5 text-xs inline-flex items-center gap-1">Open link ↗</a>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- The full link is displayed in full, wrapped across lines so no characters are hidden or truncated --}}
                                                        <div class="p-3 rounded-lg bg-white border border-black/[0.06] font-mono text-[12px] leading-relaxed break-all select-all text-zinc-800">
                                                            @if ((filter_var($link, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true)))
                                                                <a href="{{ $link }}" target="_blank" rel="noopener" class="text-accent hover:underline">{{ $link }}</a>
                                                            @else
                                                                {{ $link }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif ($item->digital_file)
                                            <div class="mt-2">
                                                <a href="{{ $item->downloadUrl() }}" class="btn-primary inline-flex items-center gap-2">
                                                    <span>Download PDF / File</span>
                                                    <span>↓</span>
                                                </a>
                                            </div>
                                        @else
                                            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-[13px] text-amber-800">
                                                ⏳ <strong>Link being prepared:</strong> Our team is assigning your personal link and will email it to <strong>{{ $order->email }}</strong> shortly.
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
                    <li class="flex justify-between py-3 text-[14px]"><span>{{ $item->displayName() }} × {{ $item->quantity }}</span><span class="font-medium">@naira($item->line_total)</span></li>
                @endforeach
                @if ($order->discount_amount > 0)
                    <li class="flex justify-between py-3 text-[14px] text-emerald-700 font-medium">
                        <span>Discount ({{ $order->discount_code }})</span>
                        <span>-@naira($order->discount_amount)</span>
                    </li>
                @endif
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
