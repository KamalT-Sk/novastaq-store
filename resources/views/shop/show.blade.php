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
                        <img x-show="active === {{ $i }}" @if ($i) x-cloak @endif src="{{ $image->url() }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-8">
                    @empty
                        <svg class="w-14 h-14 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z"/></svg>
                    @endforelse
                </div>
                @if ($product->images->count() > 1)
                    <div class="flex gap-3 mt-3">
                        @foreach ($product->images as $i => $image)
                            <button type="button" @click="active = {{ $i }}" :class="active === {{ $i }} ? 'border-ink' : 'border-black/[0.08]'" class="w-20 h-20 rounded-xl border bg-zinc-50 overflow-hidden" aria-label="Show image {{ $i + 1 }}">
                                <img src="{{ $image->url() }}" alt="" class="w-full h-full object-contain p-2">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h1 class="text-4xl font-semibold tracking-[-0.03em] leading-tight mb-3">{{ $product->name }}</h1>
                @if ($product->short_description)<p class="text-[17px] text-zinc-500 mb-6">{{ $product->short_description }}</p>@endif

                <div class="mb-8">
                    @if ($product->isPriceOnDemand())
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-3xl font-semibold tracking-[-0.02em] text-emerald-700">Ask for price</span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                On Demand
                            </span>
                            @if ($product->isService() && $product->stock !== null)
                                <span class="text-sm font-medium text-zinc-600 bg-zinc-100 px-3 py-1 rounded-full">
                                    {{ $product->stock }} {{ \Illuminate\Support\Str::plural('spot', $product->stock) }} available
                                </span>
                            @endif
                        </div>
                    @elseif ($product->hasVariants() && $product->minPrice() !== $product->maxPrice())
                        <p class="flex items-baseline gap-3">
                            <span class="text-3xl font-semibold tracking-[-0.02em]">From @naira($product->minPrice())</span>
                            @if (! $product->isService() && $product->compare_at_price > $product->price)
                                <span class="text-zinc-400 line-through text-lg">@naira($product->compare_at_price)</span>
                            @endif
                        </p>
                    @else
                        <p class="flex items-baseline gap-3">
                            <span class="text-3xl font-semibold tracking-[-0.02em]">@naira($product->price)</span>
                            @if (! $product->isService() && $product->compare_at_price > $product->price)
                                <span class="text-zinc-400 line-through text-lg">@naira($product->compare_at_price)</span>
                            @endif
                        </p>
                    @endif
                </div>

                @php
                    try {
                        $activeVariants = $product->activeVariants;
                        $activeVariantsData = $activeVariants->map(fn ($v) => [
                            'id' => $v->id,
                            'name' => $v->name,
                            'price' => $v->price,
                            'priceFormatted' => \App\Support\Money::naira($v->price),
                            'compareAtFormatted' => $v->compare_at_price ? \App\Support\Money::naira($v->compare_at_price) : null,
                        ])->values()->all();
                        $firstVariant = $activeVariants->first();
                    } catch (\Throwable) {
                        $activeVariants = collect();
                        $activeVariantsData = [];
                        $firstVariant = null;
                    }
                    $initPriceFormatted = $firstVariant ? \App\Support\Money::naira($firstVariant->price) : \App\Support\Money::naira($product->price);
                    $initPriceKobo = $firstVariant ? $firstVariant->price : $product->price;
                    $initCompareAt = $firstVariant
                        ? ($firstVariant->compare_at_price ? \App\Support\Money::naira($firstVariant->compare_at_price) : null)
                        : ($product->compare_at_price ? \App\Support\Money::naira($product->compare_at_price) : null);
                @endphp

                @if ($product->isPriceOnDemand())
                    <div class="panel p-6 sm:p-7 space-y-5 bg-gradient-to-br from-white via-zinc-50/60 to-emerald-50/20 border border-emerald-500/25 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.948.814 2.8.814 3.18 0 5.766-2.587 5.766-5.766 0-3.18-2.586-5.766-5.77-5.766zm9.969 5.766c0 5.503-4.477 9.97-9.969 9.97-1.745 0-3.376-.454-4.797-1.246l-5.234 1.371 1.396-5.105c-.896-1.488-1.41-3.23-1.41-5.09 0-5.503 4.478-9.97 10.045-9.97 5.492 0 9.969 4.467 9.969 9.97z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-ink">Price Available on Request</h3>
                                <p class="text-[14px] text-zinc-600 mt-1 leading-relaxed">
                                    Pricing for <strong>{{ $product->name }}</strong> is dynamically quoted based on current batch and slot availability to guarantee the best rate.
                                </p>
                                @if ($product->isService() && $product->stock !== null)
                                    <div class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80">
                                        ⚡ Only {{ $product->stock }} {{ \Illuminate\Support\Str::plural('spot', $product->stock) }} remaining in current batch
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="pt-2">
                            <a href="{{ $product->whatsappInquiryUrl() }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn-primary w-full h-12 text-[15px] font-semibold bg-[#25D366] hover:bg-[#20ba5a] text-white border-0 flex items-center justify-center gap-2.5 shadow-md shadow-emerald-500/10 transition-colors">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.983z"/>
                                </svg>
                                <span>Ask for Price on WhatsApp</span>
                            </a>
                            <p class="text-center text-[12px] text-zinc-400 mt-2.5">
                                Direct WhatsApp chat with Novastaq concierge · Fast response
                            </p>
                        </div>
                    </div>
                @elseif ($product->isService())
                    @php($me = auth()->user())
                    <form method="POST" action="{{ route('service.buy', $product) }}" x-data="productBuyForm({{ Js::from($activeVariantsData) }}, '{{ $initPriceFormatted }}', '{{ $initCompareAt }}', {{ $product->id }}, {{ $initPriceKobo }})" class="panel p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selectedVariantId">

                        @if ($activeVariants->isNotEmpty())
                            <div>
                                <label class="label mb-2 block">Choose Option / Plan</label>
                                <div class="grid gap-2.5 sm:grid-cols-{{ min(3, max(1, $activeVariants->count())) }}">
                                    @foreach ($activeVariants as $v)
                                        <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all text-left"
                                               :class="selectedVariantId === {{ $v->id }} ? 'border-ink bg-zinc-50 ring-1 ring-ink/10 shadow-xs' : 'border-black/10 hover:border-black/20 bg-white'"
                                               @click="selectVariant({ id: {{ $v->id }}, price: {{ $v->price }}, priceFormatted: '@naira($v->price)', compareAtFormatted: '{{ $v->compare_at_price ? \App\Support\Money::naira($v->compare_at_price) : '' }}' })">
                                            <input type="radio" name="_variant_choice" value="{{ $v->id }}" class="sr-only" :checked="selectedVariantId === {{ $v->id }}">
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="text-[13px] font-semibold text-ink leading-snug">{{ $v->name }}</span>
                                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5"
                                                     :class="selectedVariantId === {{ $v->id }} ? 'border-ink bg-ink text-white' : 'border-zinc-300'">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedVariantId === {{ $v->id }}"></div>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex items-baseline gap-1.5">
                                                <span class="text-base font-bold text-ink">@naira($v->price)</span>
                                                @if ($v->compare_at_price && $v->compare_at_price > $v->price)
                                                    <span class="text-[11px] text-zinc-400 line-through">@naira($v->compare_at_price)</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="account_details" class="label">{{ $product->accountPrompt() }}</label>
                            <input id="account_details" name="account_details" value="{{ old('account_details') }}" required class="field h-11 @error('account_details') !border-red-500 @enderror" placeholder="e.g. @yourhandle or you@email.com">
                            @error('account_details')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            <p class="text-[12px] text-zinc-400 mt-1.5">This is the account we'll activate the plan on.</p>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_name" class="label">Your name</label>
                                <input id="customer_name" name="customer_name" value="{{ old('customer_name', $me?->name ?? session('customer_name')) }}" required autocomplete="name" class="field h-11">
                                @error('customer_name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="label">Phone or WhatsApp</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone', $me?->phone ?? session('customer_phone')) }}" required autocomplete="tel" class="field h-11">
                                @error('phone')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="email" class="label">Your email <span class="font-normal text-zinc-400">(for your receipt)</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email', $me?->email ?? session('customer_email')) }}" required autocomplete="email" class="field h-11">
                            @error('email')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="notes" class="label flex justify-between">Notes <span class="font-normal text-zinc-400">Optional</span></label>
                            <input id="notes" name="notes" value="{{ old('notes') }}" class="field h-11" placeholder="Anything we should know">
                        </div>

                        {{-- Discount Promo Code --}}
                        <div>
                            <div x-show="!discountApplied" class="space-y-1.5">
                                <label class="label flex justify-between">Discount code <span class="font-normal text-zinc-400">Optional</span></label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="promoCode" @keydown.enter.prevent="applyPromo()" placeholder="Enter discount code" class="field h-11 text-[13px] uppercase font-mono tracking-wider flex-1 bg-white">
                                    <button type="button" @click="applyPromo()" :disabled="checkingPromo" class="btn-secondary h-11 px-5 text-[13px] font-medium shrink-0">
                                        <span x-show="!checkingPromo">Apply</span>
                                        <span x-show="checkingPromo" x-cloak>Checking...</span>
                                    </button>
                                </div>
                                <p x-show="promoError" x-text="promoError" x-cloak class="text-[12px] text-red-600"></p>
                            </div>

                            <div x-show="discountApplied" x-cloak class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-[13px] flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-emerald-800" x-text="discountCode"></span>
                                    <span class="text-emerald-700 font-semibold ml-1" x-text="'(' + discountPercent + '% OFF)'"></span>
                                    <div class="text-[12px] text-emerald-600 mt-0.5" x-text="'You save ' + discountAmountFormatted + '! Regular: ' + currentPriceFormatted"></div>
                                </div>
                                <button type="button" @click="removePromo()" class="text-xs text-zinc-400 hover:text-red-600 underline">Remove</button>
                            </div>
                            <input type="hidden" name="discount_code" :value="discountApplied ? discountCode : ''">
                        </div>

                        @if (count($availableGateways) > 0)
                        <div>
                            <label class="label block mb-2">Payment method</label>
                            <div class="grid sm:grid-cols-{{ min(3, max(1, count($availableGateways))) }} gap-2.5">
                                @foreach ($availableGateways as $name => $gw)
                                <label class="relative flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all text-left"
                                       :class="paymentMethod === '{{ $name }}' ? 'border-ink bg-zinc-50 shadow-sm' : 'border-black/10 hover:border-black/20'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="radio" name="payment_method" value="{{ $name }}" x-model="paymentMethod" class="w-4 h-4 text-ink focus:ring-black">
                                        <div>
                                            <span class="block text-[13px] font-medium leading-none">{{ $gw->label() }}</span>
                                            <span class="block text-[11px] text-zinc-500 mt-1">{{ $gw->badge() }}</span>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <button class="btn-primary w-full">
                            <span x-text="'Pay ' + finalPriceFormatted + ' with ' + ({
                                @foreach ($availableGateways as $name => $gw)
                                    '{{ $name }}': '{{ $gw->label() }}',
                                @endforeach
                            }[paymentMethod] || 'Payment Gateway')">Pay @naira($product->price) with {{ $availableGateways[old('payment_method', array_key_first($availableGateways) ?: 'paystack')]?->label() ?? 'Paystack' }}</span>
                        </button>
                        @else
                        <div class="p-3.5 rounded-xl bg-amber-50 text-amber-800 text-[13px] border border-amber-200">
                            Online checkout is temporarily paused. Please <a href="{{ config('store.whatsapp_url') }}" target="_blank" class="underline font-medium">contact us on WhatsApp</a> to activate this plan.
                        </div>
                        @endif
                        <p class="text-[12px] text-zinc-400 text-center">One-time payment. We activate it and confirm with you. By paying you agree to our <a href="{{ config('store.main_site') }}/terms" target="_blank" class="underline">Terms</a>.</p>
                    </form>
                @elseif ($product->isDigital())
                    @php($me = auth()->user())
                    <form method="POST" action="{{ route('digital.buy', $product) }}" x-data="productBuyForm({{ Js::from($activeVariantsData) }}, '{{ $initPriceFormatted }}', '{{ $initCompareAt }}', {{ $product->id }}, {{ $initPriceKobo }})" class="panel p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selectedVariantId">

                        @if ($activeVariants->isNotEmpty())
                            <div>
                                <label class="label mb-2 block">Choose Option / Plan</label>
                                <div class="grid gap-2.5 sm:grid-cols-{{ min(3, max(1, $activeVariants->count())) }}">
                                    @foreach ($activeVariants as $v)
                                        <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all text-left"
                                               :class="selectedVariantId === {{ $v->id }} ? 'border-ink bg-zinc-50 ring-1 ring-ink/10 shadow-xs' : 'border-black/10 hover:border-black/20 bg-white'"
                                               @click="selectVariant({ id: {{ $v->id }}, price: {{ $v->price }}, priceFormatted: '@naira($v->price)', compareAtFormatted: '{{ $v->compare_at_price ? \App\Support\Money::naira($v->compare_at_price) : '' }}' })">
                                            <input type="radio" name="_variant_choice_digital" value="{{ $v->id }}" class="sr-only" :checked="selectedVariantId === {{ $v->id }}">
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="text-[13px] font-semibold text-ink leading-snug">{{ $v->name }}</span>
                                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5"
                                                     :class="selectedVariantId === {{ $v->id }} ? 'border-ink bg-ink text-white' : 'border-zinc-300'">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedVariantId === {{ $v->id }}"></div>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex items-baseline gap-1.5">
                                                <span class="text-base font-bold text-ink">@naira($v->price)</span>
                                                @if ($v->compare_at_price && $v->compare_at_price > $v->price)
                                                    <span class="text-[11px] text-zinc-400 line-through">@naira($v->compare_at_price)</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($product->digital_type === 'pool' && $product->availableDigitalKeysCount() > 0)
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-[13px] text-emerald-800 flex items-center gap-2">
                                <span class="font-bold text-emerald-600">⚡ Instant Delivery:</span> Your personal link will be delivered on-screen and to your email immediately upon payment.
                            </div>
                        @elseif ($product->digital_type === 'file')
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-[13px] text-emerald-800 flex items-center gap-2">
                                <span class="font-bold text-emerald-600">⚡ Instant Access:</span> Download your file immediately on-screen and via email after payment.
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-[13px] text-amber-800 flex items-center gap-2">
                                <span class="font-bold text-amber-600">⏳ Digital Order:</span> Your unique personal link will be assigned and emailed to you shortly after payment.
                            </div>
                        @endif

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                 <label for="customer_name" class="label">Your name</label>
                                 <input id="customer_name" name="customer_name" value="{{ old('customer_name', $me?->name ?? session('customer_name')) }}" required autocomplete="name" class="field h-11">
                                 @error('customer_name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                 <label for="phone" class="label">Phone or WhatsApp</label>
                                 <input id="phone" name="phone" type="tel" value="{{ old('phone', $me?->phone ?? session('customer_phone')) }}" required autocomplete="tel" class="field h-11">
                                 @error('phone')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="email" class="label">Your email <span class="font-normal text-emerald-700">(your link is sent here)</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email', $me?->email ?? session('customer_email')) }}" required autocomplete="email" class="field h-11">
                            @error('email')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="notes" class="label flex justify-between">Notes <span class="font-normal text-zinc-400">Optional</span></label>
                            <input id="notes" name="notes" value="{{ old('notes') }}" class="field h-11" placeholder="Anything we should know">
                        </div>

                        {{-- Discount Promo Code --}}
                        <div>
                            <div x-show="!discountApplied" class="space-y-1.5">
                                <label class="label flex justify-between">Discount code <span class="font-normal text-zinc-400">Optional</span></label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="promoCode" @keydown.enter.prevent="applyPromo()" placeholder="Enter discount code" class="field h-11 text-[13px] uppercase font-mono tracking-wider flex-1 bg-white">
                                    <button type="button" @click="applyPromo()" :disabled="checkingPromo" class="btn-secondary h-11 px-5 text-[13px] font-medium shrink-0">
                                        <span x-show="!checkingPromo">Apply</span>
                                        <span x-show="checkingPromo" x-cloak>Checking...</span>
                                    </button>
                                </div>
                                <p x-show="promoError" x-text="promoError" x-cloak class="text-[12px] text-red-600"></p>
                            </div>

                            <div x-show="discountApplied" x-cloak class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-[13px] flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-emerald-800" x-text="discountCode"></span>
                                    <span class="text-emerald-700 font-semibold ml-1" x-text="'(' + discountPercent + '% OFF)'"></span>
                                    <div class="text-[12px] text-emerald-600 mt-0.5" x-text="'You save ' + discountAmountFormatted + '! Regular: ' + currentPriceFormatted"></div>
                                </div>
                                <button type="button" @click="removePromo()" class="text-xs text-zinc-400 hover:text-red-600 underline">Remove</button>
                            </div>
                            <input type="hidden" name="discount_code" :value="discountApplied ? discountCode : ''">
                        </div>

                        @if (count($availableGateways) > 0)
                        <div>
                            <label class="label block mb-2">Payment method</label>
                            <div class="grid sm:grid-cols-{{ min(3, max(1, count($availableGateways))) }} gap-2.5">
                                @foreach ($availableGateways as $name => $gw)
                                <label class="relative flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all text-left"
                                       :class="paymentMethod === '{{ $name }}' ? 'border-ink bg-zinc-50 shadow-sm' : 'border-black/10 hover:border-black/20'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="radio" name="payment_method" value="{{ $name }}" x-model="paymentMethod" class="w-4 h-4 text-ink focus:ring-black">
                                        <div>
                                            <span class="block text-[13px] font-medium leading-none">{{ $gw->label() }}</span>
                                            <span class="block text-[11px] text-zinc-500 mt-1">{{ $gw->badge() }}</span>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <button class="btn-primary w-full">
                            <span x-text="'Pay ' + finalPriceFormatted + ' with ' + ({
                                @foreach ($availableGateways as $name => $gw)
                                    '{{ $name }}': '{{ $gw->label() }}',
                                @endforeach
                            }[paymentMethod] || 'Payment Gateway')">Pay @naira($product->price) with {{ $availableGateways[old('payment_method', array_key_first($availableGateways) ?: 'paystack')]?->label() ?? 'Paystack' }}</span>
                        </button>
                        @else
                        <div class="p-3.5 rounded-xl bg-amber-50 text-amber-800 text-[13px] border border-amber-200">
                            Online checkout is temporarily paused. Please <a href="{{ config('store.whatsapp_url') }}" target="_blank" class="underline font-medium">contact us on WhatsApp</a> to purchase.
                        </div>
                        @endif
                        <p class="text-[12px] text-zinc-400 text-center">Instant digital access · No delivery fee. By paying you agree to our <a href="{{ config('store.main_site') }}/terms" target="_blank" class="underline">Terms</a>.</p>
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

    <script>
        function productBuyForm(variants, initialPriceFormatted, initialCompareAtFormatted, productId, initialSubtotalKobo) {
            const defaultVariant = (variants && variants.length > 0) ? variants[0] : null;
            return {
                variants: variants || [],
                selectedVariantId: defaultVariant ? defaultVariant.id : '',
                currentPriceKobo: defaultVariant ? defaultVariant.price : initialSubtotalKobo,
                currentPriceFormatted: defaultVariant ? defaultVariant.priceFormatted : initialPriceFormatted,
                currentCompareAtFormatted: defaultVariant ? defaultVariant.compareAtFormatted : initialCompareAtFormatted,
                paymentMethod: '{{ old('payment_method', array_key_first($availableGateways) ?: 'paystack') }}',
                showPromo: false,
                promoCode: '',
                discountApplied: false,
                discountCode: '',
                discountPercent: 0,
                discountAmountFormatted: '',
                finalPriceFormatted: defaultVariant ? defaultVariant.priceFormatted : initialPriceFormatted,
                promoError: '',
                checkingPromo: false,

                selectVariant(v) {
                    this.selectedVariantId = v.id;
                    this.currentPriceKobo = v.price;
                    this.currentPriceFormatted = v.priceFormatted;
                    this.currentCompareAtFormatted = v.compareAtFormatted;
                    if (this.discountApplied) {
                        this.recalculateDiscount();
                    } else {
                        this.finalPriceFormatted = v.priceFormatted;
                    }
                },

                async recalculateDiscount() {
                    if (!this.discountApplied || !this.discountCode) return;
                    try {
                        const res = await fetch('{{ route('discount.check') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                code: this.discountCode,
                                product_id: productId,
                                subtotal: this.currentPriceKobo,
                                variant_id: this.selectedVariantId || null,
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.valid) {
                            this.discountAmountFormatted = data.discount_formatted;
                            this.finalPriceFormatted = data.final_price_formatted;
                        }
                    } catch (e) {
                        // ignore network glitches during recalculation
                    }
                },

                async applyPromo() {
                    this.promoError = '';
                    const code = this.promoCode.trim().toUpperCase();
                    if (!code) {
                        this.promoError = 'Enter a discount code.';
                        return;
                    }
                    this.checkingPromo = true;
                    try {
                        const res = await fetch('{{ route('discount.check') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                code: code,
                                product_id: productId,
                                subtotal: this.currentPriceKobo,
                                variant_id: this.selectedVariantId || null,
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.valid) {
                            this.discountApplied = true;
                            this.discountCode = data.code;
                            this.discountPercent = data.percent_off;
                            this.discountAmountFormatted = data.discount_formatted;
                            this.finalPriceFormatted = data.final_price_formatted;
                            this.promoCode = '';
                            this.promoError = '';
                        } else {
                            this.promoError = data.message || 'Invalid discount code.';
                        }
                    } catch (e) {
                        this.promoError = 'Could not verify code. Please try again.';
                    } finally {
                        this.checkingPromo = false;
                    }
                },

                removePromo() {
                    this.discountApplied = false;
                    this.discountCode = '';
                    this.discountPercent = 0;
                    this.discountAmountFormatted = '';
                    this.finalPriceFormatted = this.currentPriceFormatted;
                    this.promoError = '';
                }
            };
        }
    </script>
</x-app-layout>
