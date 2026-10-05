<x-admin-layout title="Dashboard">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Dashboard</h1>
        <a href="{{ route('admin.products.create') }}" class="btn-primary h-10">Add product</a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach ([
            ['Revenue this month', \App\Support\Money::naira($revenueMonth)],
            ['Paid orders today', $ordersToday],
            ['Orders to fulfil', $toFulfil],
            ['Active subscriptions', $activeSubs],
        ] as [$label, $value])
            <div class="panel p-5">
                <p class="text-[13px] text-zinc-500 mb-2">{{ $label }}</p>
                <p class="text-2xl font-semibold tracking-[-0.02em]">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        <section class="panel">
            <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
                <h2 class="font-semibold">Recent orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-[13px] font-medium hover:underline">All orders</a>
            </div>
            @forelse ($recentOrders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-4 px-5 py-3 border-b border-black/[0.04] last:border-0 hover:bg-zinc-50 text-[14px]">
                    <span><span class="font-medium">{{ $order->number }}</span> <span class="text-zinc-500">· {{ $order->customer_name }}</span></span>
                    <span class="flex items-center gap-3">
                        @include('admin.partials.order-badge', ['order' => $order])
                        <span class="font-medium w-28 text-right">@naira($order->total)</span>
                    </span>
                </a>
            @empty
                <p class="p-5 text-[14px] text-zinc-500">No orders yet.</p>
            @endforelse
        </section>

        <div class="space-y-6">
            <section class="panel">
                <h2 class="font-semibold p-5 border-b border-black/[0.06]">Low stock</h2>
                @forelse ($lowStock as $p)
                    <a href="{{ route('admin.products.edit', $p) }}" class="flex justify-between px-5 py-3 border-b border-black/[0.04] last:border-0 hover:bg-zinc-50 text-[14px]">
                        <span>{{ $p->name }}</span><span class="{{ $p->stock ? 'text-amber-700' : 'text-red-600' }} font-medium">{{ $p->stock }} left</span>
                    </a>
                @empty
                    <p class="p-5 text-[14px] text-zinc-500">Nothing running low.</p>
                @endforelse
            </section>

            <section class="panel">
                <h2 class="font-semibold p-5 border-b border-black/[0.06]">Subscriptions needing attention</h2>
                @forelse ($attentionSubs as $s)
                    <div class="px-5 py-3 border-b border-black/[0.04] last:border-0 text-[14px]">
                        <p>{{ $s->email }}</p><p class="text-[13px] text-zinc-500">{{ $s->product->name }} · payment failed</p>
                    </div>
                @empty
                    <p class="p-5 text-[14px] text-zinc-500">All payments are going through.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-admin-layout>
