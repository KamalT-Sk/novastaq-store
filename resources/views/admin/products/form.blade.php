@php($editing = $product->exists)
@php($naira = fn ($kobo) => $kobo === null ? '' : rtrim(rtrim(number_format($kobo / 100, 2, '.', ''), '0'), '.'))
<x-admin-layout :title="$editing ? $product->name : 'New product'">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.products.index') }}" class="text-[13px] text-zinc-500 hover:text-ink">← Products</a>
            <h1 class="text-2xl font-semibold tracking-[-0.02em] mt-1">{{ $editing ? $product->name : 'New product' }}</h1>
        </div>
        @if ($editing && $product->is_active)
            <a href="{{ route('product', $product) }}" target="_blank" class="btn-secondary h-10">View in store ↗</a>
        @endif
    </div>

    @if (! empty($justAdded))
        <div role="status" class="mb-6 rounded-xl px-4 py-3 text-[14px] bg-emerald-50 text-emerald-800 border border-emerald-100 flex flex-wrap items-center justify-between gap-3">
            <span>✓ <strong>{{ $justAdded->name }}</strong> added. The form is ready for the next one.</span>
            <span class="flex gap-4">
                <a href="{{ route('admin.products.edit', $justAdded) }}" class="underline underline-offset-4">Edit it</a>
                <a href="{{ route('product', $justAdded) }}" target="_blank" class="underline underline-offset-4">View in store ↗</a>
                <a href="{{ route('admin.products.index') }}" class="underline underline-offset-4">All products</a>
            </span>
        </div>
    @endif

    {{-- Model suggestions for the name field, per category (config/catalogue.php) --}}
    @foreach ($categories as $c)
        @if ($models = config('catalogue.'.$c->slug))
            <datalist id="models-{{ $c->id }}">
                @foreach ($models as $model)<option value="{{ $model }}">@endforeach
            </datalist>
        @endif
    @endforeach

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          x-data="{ type: '{{ old('type', $product->type) }}', cat: '{{ old('category_id', $product->category_id ?? $categories->first()?->id) }}' }" class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6">
            <section class="panel p-6 space-y-5">
                <div>
                    <label class="label" for="name">Name <span class="font-normal text-zinc-400">(start typing for model suggestions)</span></label>
                    <input id="name" name="name" value="{{ old('name', $product->name) }}" required autocomplete="off" :list="'models-' + cat" class="field h-11" @if (! $editing) autofocus @endif>
                    @error('name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="short_description">Short description <span class="font-normal text-zinc-400">(one line, shown on cards)</span></label>
                    <input id="short_description" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="255" class="field h-11">
                </div>
                <div>
                    <label class="label" for="description">Full description</label>
                    <textarea id="description" name="description" rows="8" class="field">{{ old('description', $product->description) }}</textarea>
                </div>
            </section>

            <section class="panel p-6">
                <h2 class="font-semibold mb-4">Images</h2>
                @if ($editing && $product->images->isNotEmpty())
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mb-5">
                        @foreach ($product->images as $img)
                            <label class="relative block rounded-xl border border-black/[0.08] bg-zinc-50 aspect-square overflow-hidden cursor-pointer group">
                                <img src="{{ asset('storage/'.$img->path) }}" alt="" class="w-full h-full object-contain p-2">
                                <span class="absolute inset-x-0 bottom-0 bg-white/90 text-[11px] text-center py-1 flex items-center justify-center gap-1">
                                    <input type="checkbox" name="remove_images[]" value="{{ $img->id }}" class="rounded"> Remove
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="images[]" multiple accept="image/*" class="block w-full text-[14px] file:mr-4 file:h-10 file:px-4 file:rounded-full file:border-0 file:bg-zinc-100 file:text-ink file:font-medium hover:file:bg-zinc-200">
                <p class="text-[12px] text-zinc-400 mt-2">JPG, PNG or WebP, up to 5 MB each. The first image is the cover. A plain white background looks best.</p>
                @error('images.*')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
            </section>
        </div>

        <div class="space-y-6 lg:sticky lg:top-8">
            <section class="panel p-6 space-y-5">
                <div>
                    <label class="label" for="type">Type</label>
                    <select id="type" name="type" x-model="type" class="field h-11">
                        <option value="physical">Physical product (gadget, device)</option>
                        <option value="service">Service / plan (we activate it, e.g. X Premium)</option>
                    </select>
                </div>
                <div x-show="type === 'service'" x-cloak>
                    <label class="label" for="account_label">Ask the customer for</label>
                    <input id="account_label" name="account_label" value="{{ old('account_label', $product->account_label) }}" maxlength="120" placeholder="e.g. X username or profile link" class="field h-11">
                    <p class="text-[12px] text-zinc-400 mt-1.5">Shown on the buy form. Leave empty for "Account email, username or link".</p>
                </div>
                <div>
                    <label class="label" for="category_id">Category</label>
                    {{-- picking a service category (e.g. AI Subscriptions) switches the type to Service --}}
                    <select id="category_id" name="category_id" required class="field h-11" x-model="cat"
                            @change="type = $event.target.selectedOptions[0].dataset.type === 'service' ? 'service' : 'physical'">
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" data-type="{{ $c->type }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="price">Price (₦)</label>
                        <input id="price" name="price" inputmode="decimal" value="{{ old('price', $naira($product->price)) }}" required placeholder="450000" class="field h-11">
                    </div>
                    <div x-show="type === 'physical'">
                        <label class="label" for="compare_at_price">Was (₦) <span class="font-normal text-zinc-400">optional</span></label>
                        <input id="compare_at_price" name="compare_at_price" inputmode="decimal" value="{{ old('compare_at_price', $naira($product->compare_at_price)) }}" class="field h-11">
                    </div>
                </div>
                @error('price')<p class="text-[13px] text-red-600 -mt-3">{{ $message }}</p>@enderror
                <div x-show="type === 'physical'">
                    <label class="label" for="stock">Stock <span class="font-normal text-zinc-400">(leave empty if not tracked)</span></label>
                    <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" class="field h-11">
                </div>
                <div class="space-y-3 pt-2">
                    <label class="flex items-center gap-3 text-[14px]"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-black/20 text-ink focus:ring-ink"> Live in the store</label>
                    <label class="flex items-center gap-3 text-[14px]"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded border-black/20 text-ink focus:ring-ink"> Feature on the home page</label>
                </div>
                <details class="text-[13px]">
                    <summary class="cursor-pointer text-zinc-500">Web address</summary>
                    <input name="slug" value="{{ old('slug', $product->slug) }}" placeholder="made from the name" class="field h-10 mt-2">
                    @error('slug')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </details>
                <button class="btn-primary w-full">{{ $editing ? 'Save changes' : 'Create product' }}</button>
            </section>
        </div>
    </form>

    @if ($editing)
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-8" onsubmit="return confirm('Delete {{ addslashes($product->name) }}? Products with orders are hidden instead.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Delete product</button>
        </form>
    @endif
</x-admin-layout>
