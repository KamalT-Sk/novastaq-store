<x-admin-layout title="Orders">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Orders</h1>
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

    <form class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $q }}" placeholder="Order number, name or email" class="field h-10 w-72">
        <select name="payment" onchange="this.form.submit()" class="field h-10 w-44">
            <option value="">Any payment</option>
            <option value="paid" @selected(request('payment') === 'paid')>Paid</option>
            <option value="unpaid" @selected(request('payment') === 'unpaid')>Awaiting payment</option>
        </select>
        <select name="status" onchange="this.form.submit()" class="field h-10 w-44">
            <option value="">Any status</option>
            @foreach (\App\Models\Order::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
    </form>

    <div class="panel overflow-x-auto">
        <table class="w-full text-[14px]">
            <thead class="text-left text-zinc-500 border-b border-black/[0.06]">
                <tr><th class="p-4 font-medium">Order</th><th class="p-4 font-medium">Customer</th><th class="p-4 font-medium">Date</th><th class="p-4 font-medium">Items</th><th class="p-4 font-medium">Total</th><th class="p-4 font-medium">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @forelse ($orders as $o)
                    <tr class="hover:bg-zinc-50 cursor-pointer" onclick="location.href='{{ route('admin.orders.show', $o) }}'">
                        <td class="p-4 font-medium"><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td>
                        <td class="p-4">{{ $o->customer_name }}<p class="text-[12px] text-zinc-400">{{ $o->state }}</p></td>
                        <td class="p-4 text-zinc-500">{{ $o->created_at->format('j M, H:i') }}</td>
                        <td class="p-4">{{ $o->items_count }}</td>
                        <td class="p-4 font-medium">@naira($o->total)</td>
                        <td class="p-4">@include('admin.partials.order-badge', ['order' => $o])</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-zinc-500">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-admin-layout>
