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
                            </div>
                            @include('admin.partials.order-badge', ['order' => $order])
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
