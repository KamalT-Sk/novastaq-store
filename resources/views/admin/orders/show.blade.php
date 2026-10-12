<x-admin-layout :title="'Order '.$order->number">
    <a href="{{ route('admin.orders.index') }}" class="text-[13px] text-zinc-500 hover:text-ink">← Orders</a>
    <div class="flex flex-wrap items-center gap-3 mt-1 mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ $order->number }}</h1>
        @include('admin.partials.order-badge', ['order' => $order])
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        <section class="panel">
            <h2 class="font-semibold p-5 border-b border-black/[0.06]">Items</h2>
            @foreach ($order->items as $item)
                <div class="flex justify-between gap-4 px-5 py-3 border-b border-black/[0.04] text-[14px]">
                    <span>
                        @if ($item->product)<a href="{{ route('admin.products.edit', $item->product) }}" class="font-medium hover:underline">{{ $item->name }}</a>@else<span class="font-medium">{{ $item->name }}</span>@endif
                        <span class="text-zinc-500">× {{ $item->quantity }} at @naira($item->unit_price)</span>
                        @if ($item->account_details)
                            <span class="block mt-2 text-zinc-500">Activate for: <span class="font-mono text-ink bg-zinc-100 px-1.5 py-0.5 rounded select-all break-all">{{ $item->account_details }}</span></span>
                        @endif
                    </span>
                    <span class="font-medium">@naira($item->line_total)</span>
                </div>
            @endforeach
            <dl class="p-5 space-y-2 text-[14px]">
                <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd>@naira($order->subtotal)</dd></div>
                @unless ($order->isService())
                    <div class="flex justify-between"><dt class="text-zinc-500">Delivery</dt><dd>@naira($order->delivery_fee)</dd></div>
                @endunless
                <div class="flex justify-between text-base font-semibold pt-2"><dt>Total</dt><dd>@naira($order->total)</dd></div>
            </dl>
        </section>

        <div class="space-y-6">
            <section class="panel p-5 text-[14px] space-y-1">
                <h2 class="font-semibold mb-3">Customer</h2>
                <p class="font-medium">{{ $order->customer_name }}</p>
                <p><a href="mailto:{{ $order->email }}" class="hover:underline">{{ $order->email }}</a></p>
                <p>
                    <a href="tel:{{ $order->phone }}" class="hover:underline">{{ $order->phone }}</a>
                    · <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/\D/', '', $order->phone)) }}" target="_blank" rel="noopener" class="text-accent hover:underline">WhatsApp</a>
                </p>
                @if ($order->address)
                    <p class="text-zinc-600 pt-2 whitespace-pre-line">{{ $order->address }}
{{ $order->city }}, {{ $order->state }}</p>
                @endif
                @if ($order->notes)<p class="text-zinc-500 pt-2 italic">“{{ $order->notes }}”</p>@endif
            </section>

            <section class="panel p-5 text-[14px] space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Payment</h2>
                    @if ($order->isPaid())
                        <span class="inline-flex items-center text-[12px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                            ✓ Paid
                        </span>
                    @else
                        <span class="inline-flex items-center text-[12px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                            Awaiting payment
                        </span>
                    @endif
                </div>

                <p class="text-zinc-600">{{ $order->isPaid() ? 'Paid '.$order->paid_at?->format('j M Y, H:i') : 'Payment has not been confirmed yet.' }}</p>
                @if ($order->payment_provider || $order->payment_reference)
                    <p class="text-zinc-400 text-[12px]">{{ ucfirst((string) ($order->payment_provider ?: 'manual')) }} reference: {{ $order->payment_reference ?: '—' }}</p>
                @endif

                @unless ($order->isPaid())
                    <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}" onsubmit="return confirm('Mark this order as paid? This will confirm payment, deduct stock, and send confirmation emails to the customer.')">
                        @csrf
                        <button type="submit" class="btn-primary w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg flex items-center justify-center gap-2">
                            <span>Mark as paid</span>
                        </button>
                    </form>
                @endunless
            </section>

            @if ($order->isService() && $order->needsAction())
                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="panel p-5">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <p class="text-[14px] text-zinc-500 mb-3">Activated the plan for the account above?</p>
                    <button class="btn-primary w-full">Mark as done</button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="panel p-5 space-y-3">
                @csrf @method('PATCH')
                <label for="status" class="font-semibold block">Fulfilment status</label>
                <select id="status" name="status" class="field h-11">
                    @foreach (\App\Models\Order::STATUSES as $s)<option value="{{ $s }}" @selected($order->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
                <button class="btn-primary w-full">Update status</button>
            </form>
        </div>
    </div>
</x-admin-layout>
