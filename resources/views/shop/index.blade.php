<x-app-layout :title="$category?->name ?? 'All products'">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12">
        <p class="eyebrow">{{ $category ? 'Category' : 'Shop' }}</p>
        <h1 class="text-4xl font-semibold tracking-[-0.03em] mb-2">{{ $category?->name ?? ($q ? "Results for “{$q}”" : 'All products') }}</h1>
        @if ($category?->description)<p class="text-zinc-500 max-w-2xl">{{ $category->description }}</p>@endif

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mt-8 mb-10 pb-6 border-b border-black/[0.06]">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('shop') }}" class="badge h-8 px-3.5 {{ $category ? '' : '!bg-ink !text-white' }}">All</a>
                @foreach ($categories as $c)
                    <a href="{{ route('category', $c) }}" class="badge h-8 px-3.5 {{ $category?->is($c) ? '!bg-ink !text-white' : 'hover:bg-zinc-200' }}">{{ $c->name }}</a>
                @endforeach
            </div>
            <form class="flex gap-2">
                @if ($q)<input type="hidden" name="q" value="{{ $q }}">@endif
                <label for="sort" class="sr-only">Sort</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="field h-9 py-0 text-[13px] rounded-full w-44">
                    <option value="">Newest</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                </select>
            </form>
        </div>

        @if ($products->isEmpty())
            <div class="panel p-10 text-center text-zinc-500">Nothing here yet. Try another category or <a href="{{ route('shop') }}" class="underline underline-offset-4 text-ink">view everything</a>.</div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-5 gap-y-10">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-12">{{ $products->links() }}</div>
        @endif
    </div>
</x-app-layout>
