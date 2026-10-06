@if (! $order->isPaid())
    <span class="badge">{{ $order->payment_status === 'failed' ? 'Payment failed' : 'Awaiting payment' }}</span>
@else
    <span class="badge {{ [
        'processing' => 'bg-indigo-50 text-accent',
        'shipped' => 'bg-amber-50 text-amber-700',
        'delivered' => 'bg-emerald-50 text-emerald-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'cancelled' => 'bg-zinc-100 text-zinc-500',
    ][$order->status] ?? '' }}">{{ ['processing' => 'In progress', 'completed' => 'Done'][$order->status] ?? ucfirst($order->status) }}</span>
@endif
