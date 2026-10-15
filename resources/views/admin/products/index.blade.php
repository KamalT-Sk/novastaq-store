<x-admin-layout title="Products">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Products</h1>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.system.catalogue') }}" onsubmit="return confirm('Add popular phones, laptops, gadgets and plans to your store? They are added HIDDEN with no price, and products you already have are skipped.')">
                @csrf<button class="btn-secondary h-10">Add starter catalogue</button>
            </form>
            <a href="{{ route('admin.products.create') }}" class="btn-primary h-10">Add product</a>
        </div>
    </div>

    @php($status = request('status'))
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ([['', 'All'], ['live', 'Live'], ['hidden', 'Hidden'], ['needs-price', 'Needs price']] as [$key, $label])
            <a href="{{ route('admin.products.index', array_filter(['status' => $key, 'q' => $q, 'category' => request('category')])) }}"
               class="badge h-8 px-3.5 {{ $status === $key || (! $status && $key === '') ? '!bg-ink !text-white' : 'hover:bg-zinc-200' }}">
                {{ $label }}@if ($key !== '') <span class="ml-1 opacity-60">{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </div>

    <form class="flex flex-wrap gap-3 mb-6">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Search by name" class="field h-10 w-64">
        <select name="category" onchange="this.form.submit()" class="field h-10 w-56">
            <option value="">All categories</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </form>

    <div class="panel overflow-x-auto">
        <table class="w-full text-[14px]">
            <thead class="text-left text-zinc-500 border-b border-black/[0.06]">
                <tr><th class="p-4 font-medium">Product</th><th class="p-4 font-medium">Category</th><th class="p-4 font-medium">Price</th><th class="p-4 font-medium">Stock</th><th class="p-4 font-medium">In the store</th></tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @forelse ($products as $p)
                    <tr class="hover:bg-zinc-50">
                        <td class="p-4">
                            <a href="{{ route('admin.products.edit', $p) }}" class="flex items-center gap-3 font-medium hover:underline">
                                <span class="w-10 h-10 rounded-lg bg-zinc-50 border border-black/[0.06] overflow-hidden shrink-0">@if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="w-full h-full object-contain p-1">@endif</span>
                                <span>
                                    {{ $p->name }}
                                    @if (! $p->coverUrl())<span class="block text-[12px] font-normal text-zinc-400">No photo yet</span>@endif
                                </span>
                            </a>
                        </td>
                        <td class="p-4 text-zinc-500">{{ $p->category->name }}</td>
                        <td class="p-4">
                            @if ($p->price > 0)
                                @naira($p->price)
                            @else
                                <a href="{{ route('admin.products.edit', $p) }}" class="badge bg-amber-50 text-amber-700 hover:bg-amber-100">Needs price</a>
                            @endif
                            @if ($p->isService())<span class="badge ml-1">Service</span>@endif
                            @if ($p->isDigital())<span class="badge ml-1 bg-purple-50 text-purple-700">Digital</span>@endif
                        </td>
                        <td class="p-4">
                            @if ($p->isService())
                                —
                            @elseif ($p->isDigital() && $p->digital_type === 'pool')
                                <span class="{{ $p->availableDigitalKeysCount() > 0 ? 'text-emerald-700 font-medium' : 'text-amber-700 font-medium' }}">
                                    {{ $p->availableDigitalKeysCount() }} links left
                                </span>
                            @elseif ($p->isDigital())
                                File / Link
                            @else
                                {{ $p->stock ?? 'Not tracked' }}
                            @endif
                        </td>
                        <td class="p-4">
                            @if (! auth()->user()->hasAccess('publish'))
                                <span class="badge {{ $p->is_active ? 'bg-emerald-50 text-emerald-700' : '' }}">{{ $p->is_active ? 'Live' : 'Hidden' }}</span>
                            @else
                            <form method="POST" action="{{ route('admin.products.toggle', $p) }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <button role="switch" aria-checked="{{ $p->is_active ? 'true' : 'false' }}" aria-label="{{ $p->is_active ? 'Hide' : 'Show' }} {{ $p->name }}"
                                        title="{{ $p->is_active ? 'Click to hide from the store' : ($p->price > 0 ? 'Click to make live' : 'Add a price first') }}"
                                        class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors {{ $p->is_active ? 'bg-accent' : 'bg-zinc-300' }}">
                                    <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform {{ $p->is_active ? 'translate-x-5' : '' }}"></span>
                                </button>
                                <span class="text-[13px] {{ $p->is_active ? 'text-ink' : 'text-zinc-400' }}">{{ $p->is_active ? 'Live' : 'Hidden' }}</span>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-zinc-500">No products here. <a href="{{ route('admin.products.create') }}" class="underline text-ink">Add one</a> or use <strong>Add starter catalogue</strong>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-admin-layout>
