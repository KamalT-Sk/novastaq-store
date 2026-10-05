<x-app-layout title="Order {{ $order->number }}">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-16">
        <div class="panel p-8 md:p-10">
            @if ($paid)
                <p class="eyebrow">Payment received</p>
                <h1 class="text-3xl font-semibold tracking-[-0.03em] mb-3">Thank you, {{ Str::before($order->customer_name, ' ') }}.</h1>
                <p class="text-zinc-500 mb-8">Your order <span class="font-medium text-ink">{{ $order->number }}</span> is confirmed. We'll contact you on {{ $order->phone }} to arrange delivery.</p>
            @else
                <p class="eyebrow">Payment not confirmed yet</p>
                <h1 class="text-3xl font-semibold tracking-[-0.03em] mb-3">We're waiting for Paystack.</h1>
                <p class="text-zinc-500 mb-8">If you completed the payment, it will be confirmed within a few minutes and we'll email {{ $order->email }}. If it didn't go through, your cart is still saved and you can <a href="{{ route('checkout') }}" class="underline underline-offset-4 text-ink">try again</a>.</p>
            @endif

            <ul class="divide-y divide-black/[0.06] border-y border-black/[0.06] mb-6">
                @foreach ($order->items as $item)
                    <li class="flex justify-between py-3 text-[14px]"><span>{{ $item->name }} × {{ $item->quantity }}</span><span class="font-medium">@naira($item->line_total)</span></li>
                @endforeach
            </ul>
            <p class="flex justify-between text-lg font-semibold mb-8"><span>Total</span><span>@naira($order->total)</span></p>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('shop') }}" class="btn-primary">Continue shopping</a>
                <a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="btn-secondary">Questions? Chat with us</a>
            </div>
        </div>
    </div>
</x-app-layout>
