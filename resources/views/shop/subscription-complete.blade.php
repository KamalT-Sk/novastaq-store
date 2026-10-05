<x-app-layout title="Subscription">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-16">
        <div class="panel p-8 md:p-10">
            @if ($subscription->status === 'active')
                <p class="eyebrow">Subscribed</p>
                <h1 class="text-3xl font-semibold tracking-[-0.03em] mb-3">You're on {{ $subscription->product->name }}.</h1>
                <p class="text-zinc-500 mb-8">We'll set up your access and contact you at {{ $subscription->email }}. Paystack will bill you automatically each {{ \App\Models\Product::INTERVALS[$subscription->product->billing_interval] ?? 'month' }}.</p>
            @else
                <p class="eyebrow">Waiting for confirmation</p>
                <h1 class="text-3xl font-semibold tracking-[-0.03em] mb-3">Almost there.</h1>
                <p class="text-zinc-500 mb-8">If you completed the payment, your subscription will show as active in your account within a few minutes.</p>
            @endif
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('dashboard') }}" class="btn-primary">Go to my account</a>
                <a href="{{ route('shop') }}" class="btn-secondary">Back to the store</a>
            </div>
        </div>
    </div>
</x-app-layout>
