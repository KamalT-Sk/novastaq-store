<x-app-layout title="My account">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 pt-12">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-12">
            <div>
                <p class="eyebrow">My account</p>
                <h1 class="text-4xl font-semibold tracking-[-0.03em]">Hi, {{ Str::before(auth()->user()->name, ' ') }}.</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('profile.edit') }}" class="btn-secondary h-10">Profile & password</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-secondary h-10">Sign out</button></form>
            </div>
        </div>

        <section>
            <h2 class="text-xl font-semibold mb-4">Orders</h2>
            @if ($orders->isEmpty())
                <p class="text-zinc-500 text-[15px]">No orders yet. <a href="{{ route('shop') }}" class="underline underline-offset-4 text-ink">Start shopping</a>.</p>
            @else
                <div class="space-y-3">
                    @foreach ($orders as $order)
                        <div class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $order->items->pluck('name')->join(', ') }}</p>
                                <p class="text-[13px] text-zinc-500">
                                    {{ $order->number }} · {{ $order->created_at->format('j M Y') }} · @naira($order->total)
                                    @foreach ($order->items->whereNotNull('account_details') as $item)
                                        · for <span class="text-ink">{{ $item->account_details }}</span>
                                    @endforeach
                                </p>
                                @if ($order->hasDigital())
                                    <div class="mt-3 space-y-2">
                                        @foreach ($order->items as $item)
                                            @if ($item->isDigital())
                                                @php($links = $item->digitalLinks())
                                                <div class="rounded-xl bg-zinc-50 border border-black/[0.06] p-3.5 text-[13px] space-y-2">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <span class="font-semibold text-ink">⚡ {{ $item->displayName() }} @if ($item->quantity > 1)<span class="text-xs text-zinc-500 font-normal">(×{{ $item->quantity }})</span>@endif</span>
                                                        @if (count($links) > 1)
                                                            <div x-data="{ copiedAll: false }">
                                                                <button type="button" @click="navigator.clipboard.writeText({{ json_encode(implode("

", $links)) }}); copiedAll = true; setTimeout(() => copiedAll = false, 2500)" class="text-xs text-emerald-700 hover:text-emerald-800 font-bold underline">
                                                                    <span x-show="!copiedAll">Copy all {{ count($links) }} links</span>
                                                                    <span x-show="copiedAll" x-cloak class="text-emerald-700 font-bold">✓ All copied!</span>
                                                                </button>
                                                            </div>
                                                        @elseif ($item->digital_file)
                                                            <a href="{{ route('orders.download', ['order' => $order->number, 'item' => $item->id]) }}" class="text-xs text-accent font-medium hover:underline">Download file ↓</a>
                                                        @elseif (empty($links))
                                                            <span class="text-xs text-amber-700 font-medium">Link being prepared</span>
                                                        @endif
                                                    </div>

                                                    @if (! empty($links))
                                                        <div class="space-y-2">
                                                            @foreach ($links as $idx => $link)
                                                                <div x-data="{ copied: false }" class="p-2.5 rounded-lg bg-white border border-black/[0.06] space-y-1.5">
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wider">
                                                                            @if (count($links) > 1) Link #{{ $idx + 1 }} @else Access Link @endif
                                                                        </span>
                                                                        <div class="flex items-center gap-2">
                                                                            <button type="button" @click="navigator.clipboard.writeText('{{ addslashes($link) }}'); copied = true; setTimeout(() => copied = false, 2500)" class="text-xs text-zinc-600 hover:text-ink font-medium underline">
                                                                                <span x-show="!copied">Copy link</span>
                                                                                <span x-show="copied" x-cloak class="text-emerald-700 font-bold">✓ Copied!</span>
                                                                            </button>
                                                                            @if (filter_var($link, FILTER_VALIDATE_URL))
                                                                                <a href="{{ $link }}" target="_blank" rel="noopener" class="text-xs text-accent font-medium hover:underline">Open link ↗</a>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <p class="font-mono text-zinc-700 text-xs select-all break-all leading-relaxed">{{ $link }}</p>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @include('admin.partials.order-badge', ['order' => $order])
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
