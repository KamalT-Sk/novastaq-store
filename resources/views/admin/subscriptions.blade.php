<x-admin-layout title="Subscriptions">
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-2">Subscriptions</h1>
    <p class="text-[14px] text-zinc-500 mb-6">Paystack bills these automatically. Status updates arrive through the webhook.</p>

    <form class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $q }}" placeholder="Customer email" class="field h-10 w-64">
        <select name="status" onchange="this.form.submit()" class="field h-10 w-48">
            <option value="">Any status</option>
            @foreach (\App\Models\Subscription::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
    </form>

    <div class="panel overflow-x-auto">
        <table class="w-full text-[14px]">
            <thead class="text-left text-zinc-500 border-b border-black/[0.06]">
                <tr><th class="p-4 font-medium">Customer</th><th class="p-4 font-medium">Plan</th><th class="p-4 font-medium">Started</th><th class="p-4 font-medium">Next payment</th><th class="p-4 font-medium">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @forelse ($subscriptions as $s)
                    <tr>
                        <td class="p-4">{{ $s->user?->name ?? '—' }}<p class="text-[12px] text-zinc-400">{{ $s->email }}</p></td>
                        <td class="p-4">{{ $s->product->name }}<p class="text-[12px] text-zinc-400">@naira($s->product->price) / {{ \App\Models\Product::INTERVALS[$s->product->billing_interval] ?? 'month' }}</p></td>
                        <td class="p-4 text-zinc-500">{{ $s->started_at?->format('j M Y') ?? '—' }}</td>
                        <td class="p-4 text-zinc-500">{{ $s->next_payment_at?->format('j M Y') ?? '—' }}</td>
                        <td class="p-4"><span class="badge {{ ['active' => 'bg-indigo-50 text-accent', 'attention' => 'bg-amber-50 text-amber-700', 'non-renewing' => 'bg-zinc-100 text-zinc-600'][$s->status] ?? '' }}">{{ ucfirst($s->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-zinc-500">No subscriptions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $subscriptions->links() }}</div>
</x-admin-layout>
