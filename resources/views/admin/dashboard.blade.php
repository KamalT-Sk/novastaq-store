<x-admin-layout title="Dashboard">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Dashboard</h1>
        <a href="{{ route('admin.products.create') }}" class="btn-primary h-10">Add product</a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach ([
            ['Revenue this month', \App\Support\Money::naira($revenueMonth)],
            ['Paid orders today', $ordersToday],
            ['Plans to activate', $servicesToDo],
            ['Devices to deliver', $devicesToShip],
        ] as [$label, $value])
            <div class="panel p-5">
                <p class="text-[13px] text-zinc-500 mb-2">{{ $label }}</p>
                <p class="text-2xl font-semibold tracking-[-0.02em]">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <section class="panel mb-6">
        <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
            <h2 class="font-semibold">Plans to activate</h2>
            <span class="text-[13px] text-zinc-500">Oldest first. Activate it, then mark it done.</span>
        </div>
        @forelse ($serviceQueue as $order)
            @foreach ($order->items as $item)
                <div class="flex flex-col md:flex-row md:items-center gap-3 px-5 py-4 border-b border-black/[0.04] last:border-0 text-[14px]">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium">{{ $item->name }}</p>
                        <p class="text-zinc-500">
                            For <span class="font-mono text-ink bg-zinc-100 px-1.5 py-0.5 rounded select-all break-all">{{ $item->account_details }}</span>
                            · {{ $order->customer_name }} · <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/\D/', '', $order->phone)) }}" target="_blank" rel="noopener" class="text-accent hover:underline">WhatsApp</a>
                            · paid {{ $order->paid_at?->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary h-9 px-4">Details</a>
                        <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button class="btn-primary h-9 px-4">Mark as done</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @empty
            <p class="p-5 text-[14px] text-zinc-500">Nothing waiting. New paid plans show up here.</p>
        @endforelse
    </section>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        <section class="panel">
            <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
                <h2 class="font-semibold">Recent orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-[13px] font-medium hover:underline">All orders</a>
            </div>
            @forelse ($recentOrders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-4 px-5 py-3 border-b border-black/[0.04] last:border-0 hover:bg-zinc-50 text-[14px]">
                    <span class="min-w-0 truncate"><span class="font-medium">{{ $order->number }}</span> <span class="text-zinc-500">· {{ $order->items->pluck('name')->join(', ') }}</span></span>
                    <span class="flex items-center gap-3 shrink-0">
                        @include('admin.partials.order-badge', ['order' => $order])
                        <span class="font-medium w-28 text-right">@naira($order->total)</span>
                    </span>
                </a>
            @empty
                <p class="p-5 text-[14px] text-zinc-500">No orders yet.</p>
            @endforelse
        </section>

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
    </div>
</x-admin-layout>
