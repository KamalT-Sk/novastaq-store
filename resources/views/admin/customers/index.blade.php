<x-admin-layout title="Customers & Users">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">Customers & Users</h1>
            <p class="text-[13px] text-zinc-500 mt-0.5">All registered users and customer buyers who have ordered from your store.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.customers.broadcast') }}" class="btn-primary inline-flex items-center gap-1.5 h-10 px-4">
                <span>✉</span>
                <span>Send Bulk Email</span>
            </a>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">Total Customers</p>
            <p class="text-2xl font-bold tracking-tight text-ink mt-1">{{ number_format($totalCount) }}</p>
            <p class="text-[11px] text-zinc-500 mt-1">Unique contacts</p>
        </div>
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">Registered Accounts</p>
            <p class="text-2xl font-bold tracking-tight text-ink mt-1">{{ number_format($registeredCount) }}</p>
            <p class="text-[11px] text-emerald-700 mt-1">Have store account</p>
        </div>
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">Guest Buyers</p>
            <p class="text-2xl font-bold tracking-tight text-ink mt-1">{{ number_format($guestsCount) }}</p>
            <p class="text-[11px] text-zinc-500 mt-1">Ordered as guest</p>
        </div>
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">Paying Customers</p>
            <p class="text-2xl font-bold tracking-tight text-emerald-600 mt-1">{{ number_format($payingCount) }}</p>
            <p class="text-[11px] text-emerald-700 mt-1">Completed paid order</p>
        </div>
    </div>

    {{-- Search & Tab filter --}}
    <div class="panel p-4 mb-6 space-y-3">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center text-zinc-400">🔍</span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search by name, email, or phone number..." class="field h-10 pl-9 text-[13px] bg-white">
            </div>
            <button class="btn-secondary h-10 px-4 text-[13px]">Search</button>
            @if ($q !== '')
                <a href="{{ route('admin.customers.index', ['tab' => $tab]) }}" class="btn-secondary h-10 px-3 text-[13px] text-zinc-500">Clear</a>
            @endif
        </form>

        <div class="flex flex-wrap gap-2 pt-1 border-t border-black/[0.04]">
            <a href="{{ route('admin.customers.index', ['tab' => 'all', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'all' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                All ({{ $totalCount }})
            </a>
            <a href="{{ route('admin.customers.index', ['tab' => 'registered', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'registered' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                Registered ({{ $registeredCount }})
            </a>
            <a href="{{ route('admin.customers.index', ['tab' => 'guests', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'guests' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                Guest Buyers ({{ $guestsCount }})
            </a>
            <a href="{{ route('admin.customers.index', ['tab' => 'paying', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'paying' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                Paying Customers ({{ $payingCount }})
            </a>
        </div>
    </div>

    {{-- Customers Table --}}
    <div class="panel overflow-hidden">
        @if ($customers->isEmpty())
            <div class="p-12 text-center text-zinc-400">
                <p class="text-3xl mb-2">👥</p>
                <p class="font-medium text-ink">No customers found</p>
                <p class="text-[13px] mt-1">{{ $q ? 'Try clearing your search terms.' : 'Customers will appear here when users register or make a purchase.' }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[13px]">
                    <thead class="bg-zinc-50 border-b border-black/[0.06] text-zinc-400 font-medium text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Account</th>
                            <th class="py-3 px-4">Orders</th>
                            <th class="py-3 px-4">Total Spent</th>
                            <th class="py-3 px-4">Latest Activity</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/[0.04]">
                        @foreach ($customers as $c)
                            <tr class="hover:bg-zinc-50/60 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 font-semibold flex items-center justify-center shrink-0 uppercase text-xs">
                                            {{ substr($c->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-ink truncate">{{ $c->name }}</p>
                                            <p class="text-[11px] text-zinc-400">Joined {{ $c->created_at?->format('j M Y') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[12px]">
                                    <div>
                                        <a href="mailto:{{ $c->email }}" class="text-ink hover:underline select-all">{{ $c->email }}</a>
                                    </div>
                                    @if ($c->phone)
                                        <div class="flex items-center gap-1.5 text-zinc-500 text-[11px] mt-0.5">
                                            <span>{{ $c->phone }}</span>
                                            <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/\D/', '', $c->phone)) }}" target="_blank" rel="noopener" class="text-emerald-600 hover:underline">WhatsApp ↗</a>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($c->is_registered)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ $c->role_label }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-zinc-100 text-zinc-600">
                                            Guest
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-medium">
                                    @if ($c->orders_count > 0)
                                        <a href="{{ route('admin.orders.index', ['q' => $c->email]) }}" class="text-accent hover:underline font-semibold">
                                            {{ $c->orders_count }} {{ Str::plural('order', $c->orders_count) }}
                                        </a>
                                    @else
                                        <span class="text-zinc-400">0 orders</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-ink">
                                    @naira($c->total_spent)
                                </td>
                                <td class="py-3.5 px-4 text-zinc-500">
                                    {{ $c->latest_order_at ? $c->latest_order_at->format('j M Y, H:i') : $c->created_at?->format('j M Y') }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if ($c->orders_count > 0)
                                        <a href="{{ route('admin.orders.index', ['q' => $c->email]) }}" class="btn-secondary h-8 px-2.5 text-xs inline-flex items-center gap-1">
                                            <span>Orders</span>
                                            <span>↗</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="p-4 border-t border-black/[0.06]">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif
    </div>
</x-admin-layout>
