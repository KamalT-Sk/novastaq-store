<x-app-layout>
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-16 pb-14 grid lg:grid-cols-[1.2fr_1fr] gap-10 items-end">
        <div>
            <p class="eyebrow">Novastaq Store</p>
            <h1 class="text-5xl sm:text-6xl font-semibold tracking-[-0.04em] leading-[1.02] mb-6">Tech for you<br>and your team.</h1>
            <p class="text-[17px] leading-relaxed text-zinc-500 max-w-lg">Genuine phones, laptops and gadgets, plus premium plans like X and ChatGPT, activated for you. Pay securely with Paystack.</p>
        </div>
        <div class="flex flex-wrap gap-3 lg:justify-end">
            <a href="{{ route('shop') }}" class="btn-primary">Shop all products</a>
            <a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="btn-secondary">Bulk order? Chat with us</a>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach ($categories as $category)
                    <a href="{{ route('category', $category) }}" class="panel p-5 hover:border-black/20 transition">
                        <p class="text-[15px] font-medium mb-1">{{ $category->name }}</p>
                        <p class="text-[13px] text-zinc-500">{{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ([['Featured', $featured], ['New in', $latest]] as [$heading, $items])
        @if ($items->isNotEmpty())
            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-20">
                <div class="flex items-end justify-between mb-8">
                    <h2 class="text-3xl font-semibold tracking-[-0.03em]">{{ $heading }}</h2>
                    <a href="{{ route('shop') }}" class="text-[14px] font-medium hover:underline underline-offset-4">View all</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10">
                    @foreach ($items as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    @if ($services->isNotEmpty())
        <section class="mt-24 py-20 bg-zinc-50 border-y border-black/[0.06]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <p class="eyebrow">Premium plans</p>
                <h2 class="text-3xl font-semibold tracking-[-0.03em] mb-3">Pay once. We activate it for you.</h2>
                <p class="text-zinc-500 mb-10">Choose a plan, tell us the account, pay with Paystack. We set it up and confirm.</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($services as $service)
                        <a href="{{ route('product', $service) }}" class="panel p-6 hover:border-black/20 transition flex flex-col">
                            <p class="text-[17px] font-semibold mb-1">{{ $service->name }}</p>
                            <p class="text-[14px] text-zinc-500 mb-6 flex-1">{{ $service->short_description }}</p>
                            <p class="flex items-center justify-between">
                                <span class="text-2xl font-semibold tracking-[-0.02em]">@naira($service->price)</span>
                                <span class="text-[13px] font-medium">Get it →</span>
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featured->isEmpty() && $latest->isEmpty() && $services->isEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16">
            <div class="panel p-10 text-center">
                <p class="text-xl font-semibold mb-2">New stock is on the way.</p>
                <p class="text-zinc-500">Products will appear here as soon as they are added. Need something now? <a href="{{ config('store.whatsapp_url') }}" class="underline underline-offset-4 text-ink">Chat with us</a>.</p>
            </div>
        </section>
    @endif
</x-app-layout>
