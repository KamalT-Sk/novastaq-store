<x-admin-layout title="Categories">
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-6">Categories</h1>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        <section class="panel divide-y divide-black/[0.06]">
            @forelse ($categories as $c)
                <form method="POST" action="{{ route('admin.categories.update', $c) }}" class="grid sm:grid-cols-[1fr_150px_70px_auto] gap-3 items-center p-4">
                    @csrf @method('PUT')
                    <div>
                        <input name="name" value="{{ $c->name }}" required class="field h-10" aria-label="Name">
                        <p class="text-[12px] text-zinc-400 mt-1">{{ $c->products_count }} {{ Str::plural('product', $c->products_count) }} · /c/{{ $c->slug }}</p>
                    </div>
                    <select name="type" class="field h-10" aria-label="Type">
                        <option value="product" @selected($c->type === 'product')>Products</option>
                        <option value="service" @selected($c->type === 'service')>Services</option>
                    </select>
                    <input name="sort" type="number" min="0" value="{{ $c->sort }}" class="field h-10" aria-label="Order" title="Display order">
                    <div class="flex gap-2">
                        <button class="btn-secondary h-10 px-4">Save</button>
                        @if (auth()->user()->hasAccess('delete'))
                            <button form="delete-{{ $c->id }}" class="btn-danger" @disabled($c->products_count)>Delete</button>
                        @endif
                    </div>
                </form>
                <form id="delete-{{ $c->id }}" method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')</form>
            @empty
                <p class="p-5 text-zinc-500 text-[14px]">No categories yet.</p>
            @endforelse
        </section>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="panel p-6 space-y-4">
            @csrf
            <h2 class="font-semibold">Add a category</h2>
            <div>
                <label class="label" for="new-name">Name</label>
                <input id="new-name" name="name" required class="field h-11" placeholder="e.g. Tablets">
                @error('name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                @error('slug')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="new-type">Holds</label>
                <select id="new-type" name="type" class="field h-11">
                    <option value="product">Physical products</option>
                    <option value="service">Services (plans we activate)</option>
                </select>
            </div>
            <div>
                <label class="label" for="new-description">Description <span class="font-normal text-zinc-400">optional</span></label>
                <textarea id="new-description" name="description" rows="2" class="field"></textarea>
            </div>
            <button class="btn-primary w-full">Add category</button>
        </form>
    </div>
</x-admin-layout>
