<x-app-layout :title="$product->name">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-8">
        <nav class="flex items-center gap-1.5 text-[13px] text-zinc-500 mb-8" aria-label="Breadcrumb">
            <a href="{{ route('shop') }}" class="hover:text-ink">Shop</a> <span>/</span>
            <a href="{{ route('category', $product->category) }}" class="hover:text-ink">{{ $product->category->name }}</a>
        </nav>

        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16">
            <div x-data="{ active: 0 }">
                <div class="aspect-square rounded-2xl bg-zinc-50 border border-black/[0.06] flex items-center justify-center overflow-hidden">
                    @forelse ($product->images as $i => $image)
                        <img x-show="active === {{ $i }}" @if ($i) x-cloak @endif src="{{ asset('storage/'.$image->path) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-8">
                    @empty
                        <svg class="w-14 h-14 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z"/></svg>
                    @endforelse
                </div>
                @if ($product->images->count() > 1)
                    <div class="flex gap-3 mt-3">
                        @foreach ($product->images as $i => $image)
                            <button type="button" @click="active = {{ $i }}" :class="active === {{ $i }} ? 'border-ink' : 'border-black/[0.08]'" class="w-20 h-20 rounded-xl border bg-zinc-50 overflow-hidden" aria-label="Show image {{ $i + 1 }}">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="" class="w-full h-full object-contain p-2">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h1 class="text-4xl font-semibold tracking-[-0.03em] leading-tight mb-3">{{ $product->name }}</h1>
                @if ($product->short_description)<p class="text-[17px] text-zinc-500 mb-6">{{ $product->short_description }}</p>@endif

                <p class="flex items-baseline gap-3 mb-8">
                    <span class="text-3xl font-semibold tracking-[-0.02em]">@naira($product->price)</span>
                    @if (! $product->isService() && $product->compare_at_price > $product->price)
                        <span class="text-zinc-400 line-through text-lg">@naira($product->compare_at_price)</span>
                    @endif
                </p>

                @if ($product->isService())
                    @php($me = auth()->user())
                    <form method="POST" action="{{ route('service.buy', $product) }}" x-data="{ paymentMethod: '{{ old('payment_method', 'paystack') }}' }" class="panel p-6 space-y-4">
                        @csrf
                        <div>
                            <label for="account_details" class="label">{{ $product->accountPrompt() }}</label>
                            <input id="account_details" name="account_details" value="{{ old('account_details') }}" required class="field h-11 @error('account_details') !border-red-500 @enderror" placeholder="e.g. @yourhandle or you@email.com">
                            @error('account_details')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            <p class="text-[12px] text-zinc-400 mt-1.5">This is the account we'll activate the plan on.</p>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_name" class="label">Your name</label>
                                <input id="customer_name" name="customer_name" value="{{ old('customer_name', $me?->name) }}" required autocomplete="name" class="field h-11">
                                @error('customer_name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="label">Phone or WhatsApp</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone', $me?->phone) }}" required autocomplete="tel" class="field h-11">
                                @error('phone')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="email" class="label">Your email <span class="font-normal text-zinc-400">(for your receipt)</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email', $me?->email) }}" required autocomplete="email" class="field h-11">
                            @error('email')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="notes" class="label flex justify-between">Notes <span class="font-normal text-zinc-400">Optional</span></label>
                            <input id="notes" name="notes" value="{{ old('notes') }}" class="field h-11" placeholder="Anything we should know">
                        </div>
                        <div>
                            <label class="label block mb-2">Payment method</label>
                            <div class="grid sm:grid-cols-2 gap-2.5">
                                <label class="relative flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all text-left"
                                       :class="paymentMethod === 'paystack' ? 'border-ink bg-zinc-50 shadow-sm' : 'border-black/10 hover:border-black/20'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="radio" name="payment_method" value="paystack" x-model="paymentMethod" class="w-4 h-4 text-ink focus:ring-black">
                                        <div>
                                            <span class="block text-[13px] font-medium leading-none">Paystack</span>
                                            <span class="block text-[11px] text-zinc-500 mt-1">Cards / Transfer</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="relative flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all text-left"
                                       :class="paymentMethod === 'tsara' ? 'border-ink bg-zinc-50 shadow-sm' : 'border-black/10 hover:border-black/20'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="radio" name="payment_method" value="tsara" x-model="paymentMethod" class="w-4 h-4 text-ink focus:ring-black">
                                        <div>
                                            <span class="block text-[13px] font-medium leading-none">Tsara</span>
                                            <span class="block text-[11px] text-zinc-500 mt-1">Crypto / Transfer</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <button class="btn-primary w-full">
                            <span x-text="'Pay @naira($product->price) with ' + (paymentMethod === 'tsara' ? 'Tsara' : 'Paystack')">Pay @naira($product->price) with Paystack</span>
                        </button>
                        <p class="text-[12px] text-zinc-400 text-center">One-time payment. We activate it and confirm with you. By paying you agree to our <a href="{{ config('store.main_site') }}/terms" target="_blank" class="underline">Terms</a>.</p>
                    </form>
                @elseif ($product->inStock())
                    <form method="POST" action="{{ route('cart.add', $product) }}" class="flex gap-3">
                        @csrf
                        <label for="quantity" class="sr-only">Quantity</label>
                        <input id="quantity" type="number" name="quantity" value="1" min="1" max="{{ $product->stock ?? 99 }}" class="field w-24 h-11 text-center">
                        <button class="btn-primary flex-1 sm:flex-none">Add to cart</button>
                    </form>
                    @if ($product->stock !== null && $product->stock <= 5)
                        <p class="text-[13px] text-amber-700 mt-3">Only {{ $product->stock }} left.</p>
                    @endif
                @else
                    <p class="badge h-9 px-4 bg-red-50 text-red-700">Out of stock</p>
                    <p class="text-[14px] text-zinc-500 mt-3"><a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="underline underline-offset-4 text-ink">Chat with us</a> and we'll tell you when it's back.</p>
                @endif

                @if ($product->description)
                    <div class="mt-10 pt-8 border-t border-black/[0.06] text-[15px] leading-relaxed text-zinc-700 whitespace-pre-line">{{ $product->description }}</div>
                @endif
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-24">
                <h2 class="text-2xl font-semibold tracking-[-0.02em] mb-8">You may also like</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10">
                    @foreach ($related as $p)
                        <x-product-card :product="$p" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
