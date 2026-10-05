<x-app-layout title="Cart">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-12">
        <h1 class="text-4xl font-semibold tracking-[-0.03em] mb-10">Your cart</h1>

        @if ($lines->isEmpty())
            <div class="panel p-10 text-center">
                <p class="text-zinc-500 mb-6">Your cart is empty.</p>
                <a href="{{ route('shop') }}" class="btn-primary">Browse products</a>
            </div>
        @else
            <form method="POST" action="{{ route('cart.update') }}" class="panel divide-y divide-black/[0.06]">
                @csrf
                @foreach ($lines as $line)
                    <div class="flex items-center gap-4 p-5">
                        <a href="{{ route('product', $line->product) }}" class="w-20 h-20 rounded-xl bg-zinc-50 border border-black/[0.06] shrink-0 flex items-center justify-center overflow-hidden">
                            @if ($line->product->coverUrl())<img src="{{ $line->product->coverUrl() }}" alt="" class="w-full h-full object-contain p-2">@endif
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('product', $line->product) }}" class="font-medium hover:underline underline-offset-4">{{ $line->product->name }}</a>
                            <p class="text-[14px] text-zinc-500">@naira($line->product->price) each</p>
                        </div>
                        <label class="sr-only" for="qty-{{ $line->product->id }}">Quantity</label>
                        <input id="qty-{{ $line->product->id }}" type="number" min="0" max="{{ $line->product->stock ?? 99 }}" name="quantities[{{ $line->product->id }}]" value="{{ $line->quantity }}" class="field w-20 h-10 text-center">
                        <p class="w-28 text-right font-semibold">@naira($line->total)</p>
                    </div>
                @endforeach
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 bg-zinc-50 rounded-b-2xl">
                    <button class="btn-secondary h-10">Update cart</button>
                    <p class="text-[13px] text-zinc-500">Set a quantity to 0 to remove an item.</p>
                </div>
            </form>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                <a href="{{ route('shop') }}" class="text-[14px] font-medium hover:underline underline-offset-4">Continue shopping</a>
                <div class="flex items-center gap-6">
                    <p class="text-lg">Subtotal <span class="font-semibold ml-2">@naira($subtotal)</span></p>
                    <a href="{{ route('checkout') }}" class="btn-primary">Checkout</a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
