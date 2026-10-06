@props(['product'])

<a href="{{ route('product', $product) }}" class="group block">
    <div class="aspect-square rounded-2xl bg-zinc-50 border border-black/[0.06] overflow-hidden flex items-center justify-center mb-4">
        @if ($product->coverUrl())
            <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-contain p-6 group-hover:scale-[1.03] transition duration-500">
        @else
            <svg class="w-10 h-10 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z"/></svg>
        @endif
    </div>
    <p class="text-[15px] font-medium text-ink leading-snug mb-1 group-hover:underline underline-offset-4">{{ $product->name }}</p>
    <p class="flex items-baseline gap-2 text-[14px]">
        <span class="font-semibold">@naira($product->price)</span>
        @if ($product->isService())
            <span class="text-zinc-500">· we activate it</span>
        @elseif ($product->compare_at_price > $product->price)
            <span class="text-zinc-400 line-through">@naira($product->compare_at_price)</span>
        @endif
    </p>
    @if (! $product->isService() && $product->stock === 0)
        <p class="text-[12px] text-red-600 mt-1">Out of stock</p>
    @endif
</a>
