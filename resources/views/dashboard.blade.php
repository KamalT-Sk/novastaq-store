<x-app-layout title="My account">
    @php($badge = ['paid' => 'bg-indigo-50 text-accent', 'active' => 'bg-indigo-50 text-accent', 'attention' => 'bg-amber-50 text-amber-700', 'cancelled' => 'bg-zinc-100 text-zinc-500', 'failed' => 'bg-red-50 text-red-700'])
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

        <section class="mb-14">
            <h2 class="text-xl font-semibold mb-4">Subscriptions</h2>
            @forelse ($subscriptions as $sub)
                <div class="panel p-5 mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $sub->product->name }}</p>
                        <p class="text-[13px] text-zinc-500">
                            @naira($sub->product->price) / {{ \App\Models\Product::INTERVALS[$sub->product->billing_interval] ?? 'month' }}
                            @if ($sub->next_payment_at) · next payment {{ $sub->next_payment_at->format('j M Y') }}@endif
                        </p>
                    </div>
                    <span class="badge {{ $badge[$sub->status] ?? '' }}">{{ ucfirst($sub->status) }}</span>
                </div>
            @empty
                <p class="text-zinc-500 text-[15px]">No subscriptions yet. <a href="{{ route('shop') }}" class="underline underline-offset-4 text-ink">See plans</a>.</p>
            @endforelse
            @if ($subscriptions->where('status', 'active')->isNotEmpty())
                <p class="text-[13px] text-zinc-500 mt-3">To cancel a subscription, use the link in your Paystack receipt email or <a href="{{ config('store.whatsapp_url') }}" class="underline underline-offset-4 text-ink">message us</a>.</p>
            @endif
        </section>

        <section>
            <h2 class="text-xl font-semibold mb-4">Orders</h2>
            @if ($orders->isEmpty())
                <p class="text-zinc-500 text-[15px]">No orders yet.</p>
            @else
                <div class="panel overflow-x-auto">
                    <table class="w-full text-[14px]">
                        <thead class="text-left text-zinc-500 border-b border-black/[0.06]">
                            <tr><th class="p-4 font-medium">Order</th><th class="p-4 font-medium">Date</th><th class="p-4 font-medium">Items</th><th class="p-4 font-medium">Total</th><th class="p-4 font-medium">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-black/[0.06]">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="p-4 font-medium">{{ $order->number }}</td>
                                    <td class="p-4 text-zinc-500">{{ $order->created_at->format('j M Y') }}</td>
                                    <td class="p-4">{{ $order->items_count }}</td>
                                    <td class="p-4">@naira($order->total)</td>
                                    <td class="p-4">
                                        @if ($order->isPaid())
                                            <span class="badge {{ $badge['paid'] }}">{{ ucfirst($order->status) }}</span>
                                        @else
                                            <span class="badge">Awaiting payment</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
