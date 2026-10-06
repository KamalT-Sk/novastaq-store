<x-app-layout title="Checkout">
    @php($user = auth()->user())
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12">
        <h1 class="text-4xl font-semibold tracking-[-0.03em] mb-10">Checkout</h1>

        <form method="POST" action="{{ route('checkout.store') }}" class="grid lg:grid-cols-[1.4fr_1fr] gap-10 items-start">
            @csrf
            <div class="panel p-6 md:p-8 space-y-5">
                <h2 class="text-lg font-semibold">Delivery details</h2>
                <div class="grid sm:grid-cols-2 gap-5">
                    @foreach ([
                        ['customer_name', 'Full name', 'text', 'name', $user?->name],
                        ['email', 'Email', 'email', 'email', $user?->email],
                        ['phone', 'Phone or WhatsApp', 'tel', 'tel', $user?->phone],
                        ['city', 'City', 'text', 'address-level2', null],
                    ] as [$field, $label, $type, $auto, $default])
                        <div>
                            <label for="{{ $field }}" class="label">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $auto }}" value="{{ old($field, $default) }}" required class="field h-11 @error($field) !border-red-500 @enderror">
                            @error($field)<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <div>
                    <label for="address" class="label">Delivery address</label>
                    <textarea id="address" name="address" rows="3" autocomplete="street-address" required class="field @error('address') !border-red-500 @enderror">{{ old('address') }}</textarea>
                    @error('address')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="state" class="label">State</label>
                    <select id="state" name="state" required class="field h-11">
                        <option value="">Choose your state</option>
                        @foreach (['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT Abuja','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $state)
                            <option @selected(old('state') === $state)>{{ $state }}</option>
                        @endforeach
                    </select>
                    @error('state')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes" class="label flex justify-between">Order notes <span class="font-normal text-zinc-400">Optional</span></label>
                    <textarea id="notes" name="notes" rows="2" class="field" placeholder="Landmarks, delivery times, company name...">{{ old('notes') }}</textarea>
                </div>
            </div>

            <aside class="panel p-6 md:p-8 lg:sticky lg:top-24">
                <h2 class="text-lg font-semibold mb-5">Order summary</h2>
                <ul class="space-y-3 mb-6">
                    @foreach ($lines as $line)
                        <li class="flex justify-between gap-4 text-[14px]">
                            <span class="text-zinc-600">{{ $line->product->name }} × {{ $line->quantity }}</span>
                            <span class="font-medium whitespace-nowrap">@naira($line->total)</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 pt-5 border-t border-black/[0.06] text-[14px]">
                    <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd>@naira($subtotal)</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Delivery</dt><dd>{{ $deliveryFee ? \App\Support\Money::naira($deliveryFee) : 'Confirmed after order' }}</dd></div>
                    <div class="flex justify-between pt-3 text-lg font-semibold"><dt>Total</dt><dd>@naira($subtotal + $deliveryFee)</dd></div>
                </dl>
                <button class="btn-primary w-full mt-6">Pay @naira($subtotal + $deliveryFee) with Paystack</button>
                <p class="text-[12px] text-zinc-400 text-center mt-3">Card, bank transfer or USSD. You'll be taken to Paystack's secure page.</p>
                <p class="text-[12px] text-zinc-400 text-center mt-2">By paying you agree to our <a href="{{ config('store.main_site') }}/terms" target="_blank" class="underline">Terms</a> and <a href="{{ config('store.main_site') }}/returns" target="_blank" class="underline">Refunds & Returns</a>.</p>
            </aside>
        </form>
    </div>
</x-app-layout>
