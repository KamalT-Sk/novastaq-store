<x-admin-layout title="Dashboard">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Dashboard</h1>
        @if (auth()->user()->hasAccess('catalogue'))
            <a href="{{ route('admin.products.create') }}" class="btn-primary h-10">Add product</a>
        @endif
    </div>

    @isset($catalogue)
    {{-- Catalogue editors: no money or customer data, just what needs doing in the catalogue --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach ($catalogue as $label => $value)
            <a href="{{ route('admin.products.index', ['status' => ['Live products' => 'live', 'Hidden' => 'hidden', 'Needs a price' => 'needs-price'][$label] ?? null]) }}" class="panel p-5 hover:border-black/20 transition-all">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[13px] text-zinc-500">{{ $label }}</p>
                    @if (in_array($label, ['Needs a price', 'Without photos']) && $value > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Action</span>
                    @endif
                </div>
                <p class="text-2xl font-semibold tracking-[-0.02em]">{{ $value }}</p>
            </a>
        @endforeach
    </div>
    <section class="panel">
        <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
            <h2 class="font-semibold">Recently edited products</h2>
            <a href="{{ route('admin.products.index') }}" class="text-[13px] font-medium hover:underline">All products</a>
        </div>
        @forelse ($recentProducts as $p)
            <a href="{{ route('admin.products.edit', $p) }}" class="flex items-center justify-between gap-4 px-5 py-3 border-b border-black/[0.04] last:border-0 hover:bg-zinc-50 text-[14px]">
                <span class="min-w-0 truncate"><span class="font-medium">{{ $p->name }}</span> <span class="text-zinc-500">· {{ $p->category?->name }}</span></span>
                <span class="badge {{ $p->is_active ? 'bg-emerald-50 text-emerald-700' : '' }}">{{ $p->is_active ? 'Live' : 'Hidden' }}</span>
            </a>
        @empty
            <p class="p-5 text-[14px] text-zinc-500">No products yet.</p>
        @endforelse
    </section>
    @else

    {{-- Urgent Action / Notification Badges Ribbon --}}
    @php($actionItemsCount = ($servicesToDo ?? 0) + ($devicesToShip ?? 0) + ($newEnquiriesCount ?? 0) + ($emptyPoolCount ?? 0))
    @if ($actionItemsCount > 0)
        <div class="mb-6 p-4 rounded-xl bg-white border border-black/[0.08] shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-zinc-500 mr-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Action Items ({{ $actionItemsCount }}):
                    </span>

                    @if (($servicesToDo ?? 0) > 0)
                        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 text-amber-900 border border-amber-200/80 hover:bg-amber-100 transition-colors">
                            <span>⚡</span>
                            <span><strong>{{ $servicesToDo }}</strong> {{ \Illuminate\Support\Str::plural('plan', $servicesToDo) }} to activate</span>
                        </a>
                    @endif

                    @if (($devicesToShip ?? 0) > 0)
                        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-900 border border-blue-200/80 hover:bg-blue-100 transition-colors">
                            <span>📦</span>
                            <span><strong>{{ $devicesToShip }}</strong> {{ \Illuminate\Support\Str::plural('device', $devicesToShip) }} to ship</span>
                        </a>
                    @endif

                    @if (($newEnquiriesCount ?? 0) > 0)
                        <a href="{{ route('admin.enquiries.index', ['tab' => 'new']) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-900 border border-emerald-200/80 hover:bg-emerald-100 transition-colors">
                            <span>✉️</span>
                            <span><strong>{{ $newEnquiriesCount }}</strong> new {{ \Illuminate\Support\Str::plural('enquiry', $newEnquiriesCount) }}</span>
                        </a>
                    @endif

                    @if (($emptyPoolCount ?? 0) > 0)
                        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-50 text-rose-900 border border-rose-200/80 hover:bg-rose-100 transition-colors">
                            <span>⚠️</span>
                            <span><strong>{{ $emptyPoolCount }}</strong> key {{ \Illuminate\Support\Str::plural('pool', $emptyPoolCount) }} empty</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="panel p-5">
            <p class="text-[13px] text-zinc-500 mb-2">Revenue this month</p>
            <p class="text-2xl font-semibold tracking-[-0.02em]">{{ \App\Support\Money::naira($revenueMonth) }}</p>
        </div>

        <div class="panel p-5">
            <p class="text-[13px] text-zinc-500 mb-2">Paid orders today</p>
            <p class="text-2xl font-semibold tracking-[-0.02em]">{{ $ordersToday }}</p>
        </div>

        <div class="panel p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[13px] text-zinc-500">Plans to activate</p>
                @if ($servicesToDo > 0)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500 text-white leading-none min-w-[18px]">{{ $servicesToDo }}</span>
                @endif
            </div>
            <p class="text-2xl font-semibold tracking-[-0.02em] {{ $servicesToDo > 0 ? 'text-amber-700' : '' }}">{{ $servicesToDo }}</p>
        </div>

        <div class="panel p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[13px] text-zinc-500">Devices to deliver</p>
                @if ($devicesToShip > 0)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-blue-600 text-white leading-none min-w-[18px]">{{ $devicesToShip }}</span>
                @endif
            </div>
            <p class="text-2xl font-semibold tracking-[-0.02em] {{ $devicesToShip > 0 ? 'text-blue-700' : '' }}">{{ $devicesToShip }}</p>
        </div>

        <div class="panel p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[13px] text-zinc-500">New Enquiries</p>
                @if (($newEnquiriesCount ?? 0) > 0)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500 text-white leading-none min-w-[18px]">{{ $newEnquiriesCount }}</span>
                @endif
            </div>
            <p class="text-2xl font-semibold tracking-[-0.02em] {{ ($newEnquiriesCount ?? 0) > 0 ? 'text-emerald-700' : '' }}">{{ $newEnquiriesCount ?? 0 }}</p>
        </div>
    </div>

    {{-- Plans to activate queue --}}
    <section class="panel mb-6">
        <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold">Plans to activate</h2>
                @if ($servicesToDo > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $servicesToDo }} pending</span>
                @endif
            </div>
            <span class="text-[13px] text-zinc-500">Oldest first. Activate it, then mark it done.</span>
        </div>
        @forelse ($serviceQueue as $order)
            @foreach ($order->items as $item)
                <div class="flex flex-col md:flex-row md:items-center gap-3 px-5 py-4 border-b border-black/[0.04] last:border-0 text-[14px]">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium">{{ $item->displayName() }}</p>
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

    {{-- Main 2-column Layout --}}
    <div class="grid lg:grid-cols-[1.5fr_1fr] gap-6 items-start">
        {{-- Left: Recent Orders --}}
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

        {{-- Right Column: Recent Enquiries & Inventory Alerts --}}
        <div class="space-y-6">
            {{-- Recent Enquiries Widget --}}
            @if (isset($recentEnquiries) && $recentEnquiries->isNotEmpty())
                <section class="panel">
                    <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold">Recent Enquiries</h2>
                            @if (($newEnquiriesCount ?? 0) > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ $newEnquiriesCount }} new</span>
                            @endif
                        </div>
                        <a href="{{ route('admin.enquiries.index') }}" class="text-[13px] font-medium hover:underline">All enquiries</a>
                    </div>
                    @foreach ($recentEnquiries as $enquiry)
                        <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="flex items-center justify-between gap-3 px-5 py-3 border-b border-black/[0.04] last:border-0 hover:bg-zinc-50 text-[13px]">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink truncate">{{ $enquiry->name ?: $enquiry->email }}</p>
                                <p class="text-zinc-500 text-xs truncate">{{ $enquiry->service ?: 'General' }} · {{ $enquiry->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="shrink-0">
                                @if ($enquiry->isNew())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">New</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-zinc-100 text-zinc-600">Replied</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </section>
            @endif

            {{-- Low stock & Empty Key Pool Alerts --}}
            @if (auth()->user()->hasAccess('catalogue'))
                <section class="panel">
                    <div class="flex items-center justify-between p-5 border-b border-black/[0.06]">
                        <h2 class="font-semibold">Inventory Alerts</h2>
                        <a href="{{ route('admin.products.index') }}" class="text-[13px] font-medium hover:underline">Products</a>
                    </div>
                    @forelse ($lowStock as $p)
                        <a href="{{ route('admin.products.edit', $p) }}" class="flex justify-between items-center px-5 py-3 border-b border-black/[0.04] last:border-0 hover:bg-zinc-50 text-[14px]">
                            <span class="truncate mr-2">{{ $p->name }}</span>
                            <span class="{{ $p->stock ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-rose-700 bg-rose-50 border-rose-200' }} border px-2 py-0.5 rounded-full text-xs font-semibold shrink-0">
                                {{ $p->stock ? $p->stock.' left' : 'Out of stock' }}
                            </span>
                        </a>
                    @empty
                        <p class="p-5 text-[14px] text-zinc-500">All products are healthy.</p>
                    @endforelse
                </section>
            @endif
        </div>
    </div>
    @endisset
</x-admin-layout>
