@php($editing = $product->exists)
@php($naira = fn ($kobo) => ! $kobo ? '' : rtrim(rtrim(number_format($kobo / 100, 2, '.', ','), '0'), '.'))
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
          x-data="{ type: '{{ old('type', $product->type) }}', digitalType: '{{ old('digital_type', $product->digital_type ?? 'pool') }}', cat: '{{ old('category_id', $product->category_id ?? $categories->first()?->id) }}' }" class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start max-w-full">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 min-w-0">
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

            @if ($editing)
                <section class="panel p-6 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-lg">Discount Codes for this product</h2>
                            <p class="text-[12px] text-zinc-500 mt-0.5">Offer percentage reductions specifically for {{ $product->name }}.</p>
                        </div>
                        <a href="{{ route('admin.discounts.index', ['product_id' => $product->id]) }}" class="text-[12px] text-zinc-500 hover:text-ink underline">Manage in discounts ↗</a>
                    </div>

                    @php($codes = $product->discountCodes()->latest()->get())
                    @if ($codes->isNotEmpty())
                        <div class="divide-y divide-black/[0.04] border border-black/[0.06] rounded-xl overflow-hidden bg-white">
                            @foreach ($codes as $dc)
                                <div class="p-3 sm:px-4 flex flex-wrap items-center justify-between gap-3 text-[13px]">
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-mono font-bold px-2 py-0.5 rounded bg-zinc-100 border border-black/[0.06] text-ink">{{ $dc->code }}</span>
                                        <span class="font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-xs">{{ $dc->percent_off }}% OFF</span>
                                        <span class="text-zinc-500 text-xs">
                                            Effective price: <strong class="text-ink">@naira(max(0, $product->price - $dc->calculateDiscount($product->price)))</strong>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="text-zinc-500">{{ $dc->uses_count }} used @if($dc->max_uses)/ {{ $dc->max_uses }}@endif</span>
                                        <button type="submit" form="toggle-dc-{{ $dc->id }}" class="underline {{ $dc->is_active ? 'text-zinc-700 font-medium' : 'text-zinc-400' }}">
                                            {{ $dc->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                        <button type="submit" form="del-dc-{{ $dc->id }}" class="text-red-600 hover:underline">Delete</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-[13px] text-zinc-500 italic">No discount codes created for this product yet.</p>
                    @endif

                    <div class="pt-3 border-t border-black/[0.06]">
                        <p class="text-[13px] font-medium text-ink mb-2">+ Add a discount code for this product</p>
                        <div class="grid sm:grid-cols-[1.3fr_1fr_1fr_auto] gap-2 items-start">
                            <input type="text" form="add-product-discount-form" name="code" required placeholder="CODE (e.g. GEMINI25)" class="field h-10 text-[13px] uppercase font-mono tracking-wider bg-white">
                            <div class="relative">
                                <input type="number" form="add-product-discount-form" name="percent_off" min="1" max="100" required placeholder="25" class="field h-10 text-[13px] pr-7 bg-white">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-zinc-400 font-medium">%</span>
                            </div>
                            <input type="number" form="add-product-discount-form" name="max_uses" min="1" placeholder="Max uses (optional)" class="field h-10 text-[13px] bg-white">
                            <button type="submit" form="add-product-discount-form" class="btn-secondary h-10 px-4 text-[13px] whitespace-nowrap">Add code</button>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <div class="space-y-6 min-w-0 lg:sticky lg:top-8">
            <section class="panel p-6 space-y-5 min-w-0">
                <div>
                    <label class="label" for="type">Type</label>
                    <select id="type" name="type" x-model="type" class="field h-11">
                        <option value="physical">Physical product (gadget, device)</option>
                        <option value="digital">Digital product (links pool, download, license key)</option>
                        <option value="service">Service / plan (we activate it, e.g. X Premium)</option>
                    </select>
                </div>
                <div x-show="type === 'service'" x-cloak>
                    <label class="label" for="account_label">Ask the customer for</label>
                    <input id="account_label" name="account_label" value="{{ old('account_label', $product->account_label) }}" maxlength="120" placeholder="e.g. X username or profile link" class="field h-11">
                    <p class="text-[12px] text-zinc-400 mt-1.5">Shown on the buy form. Leave empty for "Account email, username or link".</p>
                </div>
                <div x-show="type === 'digital'" x-cloak class="space-y-4 rounded-xl bg-zinc-50 border border-black/[0.06] p-4 min-w-0 overflow-hidden">
                    <div>
                        <label class="label" for="digital_type">Delivery Method</label>
                        <select id="digital_type" name="digital_type" x-model="digitalType" class="field h-10 bg-white">
                            <option value="pool">Unique Links / Codes Pool (dispense one per buyer)</option>
                            <option value="file">Fixed Resource (same file or link for all buyers)</option>
                        </select>
                    </div>

                    {{-- Unique link pool --}}
                    <div x-show="digitalType === 'pool'" class="space-y-3"
                         x-data="{
                             rawKeys: '',
                             get parsed() {
                                 let val = this.rawKeys.trim();
                                 if (!val) return [];
                                 if (/https?:\/\//i.test(val)) {
                                     val = val.replace(/([^\r\n\s])(https?:\/\/)/gi, '$1\n$2')
                                              .replace(/[ \t]+(https?:\/\/)/gi, '\n$1');
                                     const lines = val.split(/\r\n|\r|\n/).map(l => l.trim()).filter(Boolean);
                                     const keys = [];
                                     let current = '';
                                     for (const line of lines) {
                                         if (/^https?:\/\//i.test(line)) {
                                             if (current) keys.push(current);
                                             current = line;
                                         } else if (current) {
                                             current += line;
                                         }
                                     }
                                     if (current) keys.push(current);
                                     return keys;
                                 }
                                 return val.split(/\r\n|\r|\n/).map(l => l.trim()).filter(Boolean);
                             }
                         }">
                        @if ($editing)
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="font-medium text-emerald-700">✓ {{ $product->availableDigitalKeysCount() }} links available in pool</span>
                                <span class="text-zinc-400">{{ $product->digitalKeys->whereNotNull('order_item_id')->count() }} delivered</span>
                            </div>
                        @endif
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="label mb-0" for="add_digital_keys">Add Links or Codes</label>
                                <span x-show="parsed.length > 0" x-cloak class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                    ⚡ <span x-text="parsed.length"></span> <span x-text="parsed.length === 1 ? 'link detected' : 'links detected'"></span>
                                </span>
                            </div>
                            <textarea id="add_digital_keys" name="add_digital_keys" rows="5" x-model="rawKeys"
                                      class="field bg-white font-mono text-[12px] leading-relaxed w-full max-w-full break-all"
                                      placeholder="Paste links here (one per line)."></textarea>
                            <p class="text-[12px] text-zinc-400 mt-1">Paste 1 or more links. If a link spans multiple lines, the system automatically joins it into one link.</p>

                            <div x-show="parsed.length > 0" x-cloak class="mt-2.5 p-2.5 rounded-lg bg-white border border-emerald-200 text-xs space-y-1.5 max-h-40 overflow-y-auto overflow-x-hidden min-w-0">
                                <p class="font-semibold text-emerald-900 mb-1">Detected <span x-text="parsed.length"></span> distinct links:</p>
                                <template x-for="(k, idx) in parsed" :key="idx">
                                    <div class="flex items-center gap-1.5 font-mono text-[11px] text-zinc-700 min-w-0">
                                        <span class="text-zinc-400 shrink-0 select-none" x-text="(idx + 1) + '.'"></span>
                                        <span class="truncate select-all flex-1 min-w-0" :title="k" x-text="k"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        @if ($editing && $product->digitalKeys->isNotEmpty())
                            <details class="text-[13px] pt-1 min-w-0">
                                <summary class="cursor-pointer text-zinc-500 hover:text-ink">View links currently in pool ({{ $product->digitalKeys->count() }})</summary>
                                <div class="mt-2 max-h-48 overflow-y-auto overflow-x-hidden rounded-lg border border-black/[0.06] bg-white divide-y divide-black/[0.04] min-w-0">
                                    @foreach ($product->digitalKeys as $key)
                                        <div class="p-2 flex items-center justify-between gap-2 text-[12px] min-w-0">
                                            <span class="font-mono truncate select-all flex-1 min-w-0 {{ $key->isAssigned() ? 'text-zinc-400 line-through' : 'text-ink' }}" :title="'{{ addslashes($key->key_content) }}'">{{ $key->key_content }}</span>
                                            @if ($key->isAssigned())
                                                <span class="badge text-[10px] bg-zinc-100 text-zinc-500 shrink-0">Delivered</span>
                                            @else
                                                <button type="submit" form="del-key-{{ $key->id }}" class="text-red-500 hover:text-red-700 shrink-0">Remove</button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>

                    {{-- Fixed file or link --}}
                    <div x-show="digitalType === 'file'" class="space-y-3">
                        <div>
                            <label class="label" for="digital_url">Resource or Access URL <span class="font-normal text-zinc-400">(optional)</span></label>
                            <input id="digital_url" name="digital_url" value="{{ old('digital_url', $product->digital_url) }}" placeholder="https://drive.google.com/... or https://..." class="field h-10 bg-white">
                        </div>
                        <div>
                            <label class="label" for="digital_file">Download File <span class="font-normal text-zinc-400">(PDF, ZIP, up to 25MB)</span></label>
                            @if ($editing && $product->digital_file)
                                <div class="mb-2 flex items-center justify-between text-[13px] bg-white p-2 rounded-lg border border-black/[0.06]">
                                    <span class="truncate font-mono text-[12px] text-zinc-600">{{ basename($product->digital_file) }}</span>
                                    <label class="text-red-600 hover:text-red-700 text-xs flex items-center gap-1 cursor-pointer">
                                        <input type="checkbox" name="remove_digital_file" value="1" class="rounded"> Delete file
                                    </label>
                                </div>
                            @endif
                            <input id="digital_file" type="file" name="digital_file" class="block w-full text-[13px] file:mr-3 file:h-9 file:px-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:text-ink hover:file:bg-zinc-300">
                        </div>
                    </div>

                    <div>
                        <label class="label" for="digital_instructions">Instructions for Customer <span class="font-normal text-zinc-400">(optional)</span></label>
                        <textarea id="digital_instructions" name="digital_instructions" rows="2" class="field bg-white text-[13px]" placeholder="e.g. Click the link to accept your Google Gemini workspace invite.">{{ old('digital_instructions', $product->digital_instructions) }}</textarea>
                    </div>
                </div>
                <div>
                    <label class="label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" required class="field h-11" x-model="cat">
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" data-type="{{ $c->type }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="price">Price (₦)</label>
                        <input id="price" name="price" inputmode="decimal" data-money-input value="{{ old('price', $naira($product->price)) }}" required placeholder="450,000" class="field h-11" x-init="formatMoneyInput($el)" @input="formatMoneyInput($el)" @keydown="handleMoneyKeydown($event)" @blur="handleMoneyBlur($event)">
                    </div>
                    <div x-show="type === 'physical'">
                        <label class="label" for="compare_at_price">Was (₦) <span class="font-normal text-zinc-400">optional</span></label>
                        <input id="compare_at_price" name="compare_at_price" inputmode="decimal" data-money-input value="{{ old('compare_at_price', $naira($product->compare_at_price)) }}" placeholder="500,000" class="field h-11" x-init="formatMoneyInput($el)" @input="formatMoneyInput($el)" @keydown="handleMoneyKeydown($event)" @blur="handleMoneyBlur($event)">
                    </div>
                </div>
                @error('price')<p class="text-[13px] text-red-600 -mt-3">{{ $message }}</p>@enderror
                @error('compare_at_price')<p class="text-[13px] text-red-600 -mt-3">{{ $message }}</p>@enderror
                <div x-show="type === 'physical'">
                    <label class="label" for="stock">Stock <span class="font-normal text-zinc-400">(leave empty if not tracked)</span></label>
                    <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" class="field h-11">
                </div>
                <div class="space-y-3 pt-2">
                    @if (auth()->user()->hasAccess('publish'))
                        <label class="flex items-center gap-3 text-[14px]"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-black/20 text-ink focus:ring-ink"> Live in the store</label>
                    @else
                        <p class="text-[13px] text-zinc-500 rounded-lg bg-zinc-50 px-3 py-2">{{ $product->is_active ? 'This product is live.' : 'Saved as hidden. The owner or a manager checks it and makes it live.' }}</p>
                    @endif
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

    @if ($editing && auth()->user()->hasAccess('delete'))
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-8" onsubmit="return confirm('Delete {{ addslashes($product->name) }}? Products with orders are hidden instead.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Delete product</button>
        </form>
    @endif

    @if ($editing && $product->digitalKeys->isNotEmpty())
        @foreach ($product->digitalKeys->whereNull('order_item_id') as $key)
            <form id="del-key-{{ $key->id }}" method="POST" action="{{ route('admin.products.keys.destroy', [$product, $key]) }}" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endif

    @if ($editing)
        <form id="add-product-discount-form" method="POST" action="{{ route('admin.products.discounts.store', $product) }}" class="hidden">
            @csrf
        </form>
        @foreach ($product->discountCodes as $dc)
            <form id="toggle-dc-{{ $dc->id }}" method="POST" action="{{ route('admin.discounts.toggle', $dc) }}" class="hidden">
                @csrf @method('PATCH')
            </form>
            <form id="del-dc-{{ $dc->id }}" method="POST" action="{{ route('admin.discounts.destroy', $dc) }}" class="hidden" onsubmit="return confirm('Delete discount code {{ $dc->code }}?')">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endif
</x-admin-layout>
