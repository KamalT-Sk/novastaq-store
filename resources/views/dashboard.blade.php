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
                                                <div class="rounded-lg bg-zinc-50 border border-black/[0.06] p-3 text-[13px]" x-data="{ copied: false }">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <span class="font-medium text-ink">⚡ {{ $item->name }}</span>
                                                        @if ($item->digital_content)
                                                            <div class="flex items-center gap-2">
                                                                <button type="button" @click="navigator.clipboard.writeText('{{ addslashes($item->digital_content) }}'); copied = true; setTimeout(() => copied = false, 2500)" class="text-xs text-zinc-600 hover:text-ink font-medium underline">
                                                                    <span x-show="!copied">Copy link</span>
                                                                    <span x-show="copied" x-cloak class="text-emerald-700 font-semibold">✓ Copied!</span>
                                                                </button>
                                                                @if (filter_var($item->digital_content, FILTER_VALIDATE_URL))
                                                                    <a href="{{ $item->digital_content }}" target="_blank" rel="noopener" class="text-xs text-accent font-medium hover:underline">Open link ↗</a>
                                                                @endif
                                                            </div>
                                                        @elseif ($item->digital_file)
                                                            <a href="{{ route('orders.download', ['order' => $order->number, 'item' => $item->id]) }}" class="text-xs text-accent font-medium hover:underline">Download file ↓</a>
                                                        @else
                                                            <span class="text-xs text-amber-700 font-medium">Link being prepared</span>
                                                        @endif
                                                    </div>
                                                    @if ($item->digital_content)
                                                        <p class="font-mono text-zinc-500 text-xs mt-1 select-all break-all">{{ $item->digital_content }}</p>
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
