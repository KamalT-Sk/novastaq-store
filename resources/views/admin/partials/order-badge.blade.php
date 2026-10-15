@if (! $order->isPaid())
    <div class="inline-flex flex-col gap-1 items-start">
        <span class="badge">{{ $order->payment_status === 'failed' ? 'Payment failed' : 'Awaiting payment' }}</span>
        @if ($order->isReminded())
            <span class="badge text-[10px] bg-purple-50 text-purple-700 border border-purple-100">⏰ Reminded</span>
        @endif
    </div>
@else
    <span class="badge {{ [
        'processing' => 'bg-indigo-50 text-accent',
        'shipped' => 'bg-amber-50 text-amber-700',
        'delivered' => 'bg-emerald-50 text-emerald-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'cancelled' => 'bg-zinc-100 text-zinc-500',
    ][$order->status] ?? '' }}">{{ ['processing' => 'In progress', 'completed' => 'Done'][$order->status] ?? ucfirst($order->status) }}</span>
@endif
