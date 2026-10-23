<x-admin-layout title="Orders">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Orders</h1>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn-secondary h-10 px-4 text-[13px] inline-flex items-center gap-1.5 hover:bg-zinc-100">
                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.5V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                <span>Export CSV</span>
            </a>

            @php($dueReminders = \App\Models\Order::where('payment_status', 'unpaid')->where('status', 'pending')->whereNull('reminded_at')->where('created_at', '<=', now()->subHours(2))->where('created_at', '>=', now()->subHours(48))->count())
            @if ($dueReminders > 0)
                <form method="POST" action="{{ route('admin.orders.reminders.batch') }}" onsubmit="return confirm('Send automated payment recovery reminders to all {{ $dueReminders }} customers whose checkout was 2+ hours ago?')">
                    @csrf
                    <button class="btn-secondary h-10 px-4 text-[13px] inline-flex items-center gap-1.5 text-amber-900 bg-amber-50 border-amber-200 hover:bg-amber-100">
                        <span>⏰</span>
                        <span>Send Recovery Reminders ({{ $dueReminders }} due)</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <form class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $q }}" placeholder="Order number, name, phone or email" class="field h-10 w-64">
        
        <select name="payment" onchange="this.form.submit()" class="field h-10 w-40">
            <option value="">Any payment</option>
            <option value="paid" @selected(request('payment') === 'paid')>Paid</option>
            <option value="unpaid" @selected(request('payment') === 'unpaid')>Awaiting payment</option>
        </select>

        <select name="status" onchange="this.form.submit()" class="field h-10 w-40">
            <option value="">Any status</option>
            @foreach (\App\Models\Order::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>

        <select name="type" onchange="this.form.submit()" class="field h-10 w-36">
            <option value="">Any type</option>
            <option value="service" @selected(request('type') === 'service')>Services</option>
            <option value="digital" @selected(request('type') === 'digital')>Digital files/keys</option>
            <option value="physical" @selected(request('type') === 'physical')>Physical goods</option>
        </select>

        <select name="gateway" onchange="this.form.submit()" class="field h-10 w-36">
            <option value="">Any gateway</option>
            <option value="paystack" @selected(request('gateway') === 'paystack')>Paystack</option>
            <option value="tsara" @selected(request('gateway') === 'tsara')>Tsara</option>
            <option value="nomba" @selected(request('gateway') === 'nomba')>Nomba</option>
        </select>

        @if (request()->hasAny(['q', 'payment', 'status', 'type', 'gateway']))
            <a href="{{ route('admin.orders.index') }}" class="btn-secondary h-10 px-3 text-[13px] text-zinc-500 inline-flex items-center">Reset</a>
        @endif
    </form>

    <div class="panel overflow-x-auto">
        <table class="w-full text-[14px]">
            <thead class="text-left text-zinc-500 border-b border-black/[0.06]">
                <tr><th class="p-4 font-medium">Order</th><th class="p-4 font-medium">Customer</th><th class="p-4 font-medium">Date</th><th class="p-4 font-medium">Items</th><th class="p-4 font-medium">Gateway</th><th class="p-4 font-medium">Total</th><th class="p-4 font-medium">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @forelse ($orders as $o)
                    <tr class="hover:bg-zinc-50 cursor-pointer" onclick="location.href='{{ route('admin.orders.show', $o) }}'">
                        <td class="p-4 font-medium"><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td>
                        <td class="p-4">
                            <span class="font-medium text-ink">{{ $o->customer_name }}</span>
                            <p class="text-[12px] text-zinc-400">{{ $o->phone ?: $o->email }} @if ($o->state) · {{ $o->state }} @endif</p>
                        </td>
                        <td class="p-4 text-zinc-500">{{ $o->created_at->format('j M, H:i') }}</td>
                        <td class="p-4">{{ $o->items_count }}</td>
                        <td class="p-4 uppercase text-xs font-semibold text-zinc-500">{{ $o->payment_gateway ?: 'paystack' }}</td>
                        <td class="p-4 font-medium">@naira($o->total)</td>
                        <td class="p-4">@include('admin.partials.order-badge', ['order' => $o])</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-zinc-500">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-admin-layout>
