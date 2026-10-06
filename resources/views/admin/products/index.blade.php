<x-admin-layout title="Products">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em]">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="btn-primary h-10">Add product</a>
    </div>

    <form class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search by name" class="field h-10 w-64">
        <select name="category" onchange="this.form.submit()" class="field h-10 w-56">
            <option value="">All categories</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </form>

    <div class="panel overflow-x-auto">
        <table class="w-full text-[14px]">
            <thead class="text-left text-zinc-500 border-b border-black/[0.06]">
                <tr><th class="p-4 font-medium">Product</th><th class="p-4 font-medium">Category</th><th class="p-4 font-medium">Price</th><th class="p-4 font-medium">Stock</th><th class="p-4 font-medium">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @forelse ($products as $p)
                    <tr class="hover:bg-zinc-50">
                        <td class="p-4">
                            <a href="{{ route('admin.products.edit', $p) }}" class="flex items-center gap-3 font-medium hover:underline">
                                <span class="w-10 h-10 rounded-lg bg-zinc-50 border border-black/[0.06] overflow-hidden shrink-0">@if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="w-full h-full object-contain p-1">@endif</span>
                                {{ $p->name }}
                            </a>
                        </td>
                        <td class="p-4 text-zinc-500">{{ $p->category->name }}</td>
                        <td class="p-4">@naira($p->price)@if ($p->isService())<span class="badge ml-2">Service</span>@endif</td>
                        <td class="p-4">{{ $p->isService() ? '—' : ($p->stock ?? 'Not tracked') }}</td>
                        <td class="p-4">
                            <span class="badge {{ $p->is_active ? 'bg-indigo-50 text-accent' : '' }}">{{ $p->is_active ? 'Live' : 'Hidden' }}</span>
                            @if ($p->is_featured)<span class="badge">Featured</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-zinc-500">No products yet. <a href="{{ route('admin.products.create') }}" class="underline text-ink">Add your first one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-admin-layout>
