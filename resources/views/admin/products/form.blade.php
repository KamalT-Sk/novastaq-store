@php
$editing = $product->exists;
$naira = fn ($kobo) => ! $kobo ? '' : rtrim(rtrim(number_format($kobo / 100, 2, '.', ','), '0'), '.');
$existingVariants = [];
if ($editing) {
    try {
        $existingVariants = $product->variants->map(fn ($v) => [
            'id' => (string) $v->id,
            'name' => $v->name,
            'price' => $naira($v->price),
            'compare_at_price' => $naira($v->compare_at_price),
            'stock' => $v->stock !== null ? (string)$v->stock : '',
            'is_active' => (bool)$v->is_active,
        ])->values()->all();
    } catch (\Throwable) {
        $existingVariants = [];
    }
}
@endphp
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
          x-data="{
              type: '{{ old('type', $product->type) }}',
              digitalType: '{{ old('digital_type', $product->digital_type ?? 'pool') }}',
              cat: '{{ old('category_id', $product->category_id ?? $categories->first()?->id) }}',
              variants: {{ Js::from(old('variants', $existingVariants)) }},
              askForPrice: {{ old('ask_for_price', (int) ($product->ask_for_price ?? 0)) ? 'true' : 'false' }},
              addVariant() {
                  this.variants.push({ id: '', name: '', price: '', compare_at_price: '', stock: '', is_active: true });
              },
              removeVariant(idx) {
                  this.variants.splice(idx, 1);
              }
          }" class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start max-w-full">
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

            {{-- Plans & Pricing Options --}}
            <section class="panel p-6 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-lg">Plans & Pricing Options</h2>
                        <p class="text-[12px] text-zinc-500 mt-0.5">
                            Use this for products with multiple subscription options or access levels (e.g. ChatGPT Plus Shared vs Personal, 1 month vs 4 months).
                        </p>
                    </div>
                    <button type="button" @click="addVariant()" class="btn-secondary h-9 px-3.5 text-xs font-semibold shrink-0">
                        + Add plan / option
                    </button>
                </div>

                <div x-show="variants.length === 0" class="rounded-xl border border-dashed border-black/10 p-5 text-center bg-zinc-50/50">
                    <p class="text-[13px] text-zinc-500">No extra plans configured. This product sells for the single fixed price set on the right.</p>
                    <button type="button" @click="addVariant()" class="mt-2 text-xs font-semibold text-ink underline hover:text-black">
                        + Click here to add plans (e.g. Shared ₦7,000, Personal ₦25,000)
                    </button>
                </div>

                <div x-show="variants.length > 0" x-cloak class="space-y-3">
                    <div class="hidden sm:grid sm:grid-cols-[1.8fr_1fr_1fr_auto] gap-3 px-2 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                        <span>Option Title</span>
                        <span>Price (₦)</span>
                        <span>Was / Compare (₦)</span>
                        <span></span>
                    </div>

                    <template x-for="(v, idx) in variants" :key="idx">
                        <div class="p-3 sm:p-2.5 rounded-xl border border-black/[0.08] bg-zinc-50/50 hover:bg-zinc-50 flex flex-col sm:grid sm:grid-cols-[1.8fr_1fr_1fr_auto] gap-2.5 items-center">
                            <input type="hidden" :name="'variants[' + idx + '][id]'" :value="v.id">
                            <div class="w-full">
                                <label class="sm:hidden text-[11px] font-medium text-zinc-500 mb-1 block">Option Title</label>
                                <input type="text"
                                       :name="'variants[' + idx + '][name]'"
                                       x-model="v.name"
                                       required
                                       placeholder="e.g. Shared account monthly"
                                       class="field h-10 text-[13px] bg-white w-full">
                            </div>
                            <div class="w-full">
                                <label class="sm:hidden text-[11px] font-medium text-zinc-500 mb-1 block">Price (₦)</label>
                                <input type="text"
                                       :name="'variants[' + idx + '][price]'"
                                       x-model="v.price"
                                       inputmode="decimal"
                                       data-money-input
                                       required
                                       placeholder="7,000"
                                       class="field h-10 text-[13px] bg-white w-full"
                                       @input="formatMoneyInput($el)"
                                       @blur="formatMoneyBlur($event)">
                            </div>
                            <div class="w-full">
                                <label class="sm:hidden text-[11px] font-medium text-zinc-500 mb-1 block">Was (₦) (optional)</label>
                                <input type="text"
                                       :name="'variants[' + idx + '][compare_at_price]'"
                                       x-model="v.compare_at_price"
                                       inputmode="decimal"
                                       data-money-input
                                       placeholder="optional"
                                       class="field h-10 text-[13px] bg-white w-full"
                                       @input="formatMoneyInput($el)"
                                       @blur="formatMoneyBlur($event)">
                            </div>
                            <div class="w-full sm:w-auto flex justify-end">
                                <button type="button"
                                        @click="removeVariant(idx)"
                                        class="h-9 w-9 rounded-lg hover:bg-red-50 text-zinc-400 hover:text-red-600 flex items-center justify-center transition"
                                        title="Remove option">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="pt-2 flex items-center justify-between">
                        <button type="button" @click="addVariant()" class="text-[13px] font-medium text-ink hover:underline">
                            + Add another plan / option
                        </button>
                        <span class="text-[11px] text-zinc-400" x-text="variants.length + ' ' + (variants.length === 1 ? 'plan' : 'plans') + ' configured'"></span>
                    </div>
                </div>
            </section>

            <section class="panel p-6" x-data="{
                marked: [],
                filesCount: 0,
                fileNames: [],
                toggleMark(id) {
                    id = Number(id);
                    const idx = this.marked.indexOf(id);
                    if (idx > -1) {
                        this.marked.splice(idx, 1);
                    } else {
                        this.marked.push(id);
                    }
                },
                isMarked(id) {
                    return this.marked.includes(Number(id));
                },
                onFilesSelected(e) {
                    const files = Array.from(e.target.files || []);
                    this.filesCount = files.length;
                    this.fileNames = files.map(f => f.name);
                },
                clearSelectedFiles() {
                    const inp = document.getElementById('product-images-input');
                    if (inp) inp.value = '';
                    this.filesCount = 0;
                    this.fileNames = [];
                },
                confirmBatchDelete(e) {
                    if (this.marked.length === 0) {
                        e.preventDefault();
                        return;
                    }
                    const count = this.marked.length;
                    if (!confirm('Are you sure you want to permanently delete ' + count + ' marked ' + (count === 1 ? 'image' : 'images') + '?')) {
                        e.preventDefault();
                        return;
                    }
                    const form = document.getElementById('batch-delete-images-form');
                    form.querySelectorAll('input[name=\'remove_images[]\']').forEach(el => el.remove());
                    this.marked.forEach(id => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'remove_images[]';
                        inp.value = id;
                        form.appendChild(inp);
                    });
                }
            }">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-semibold text-lg">Images</h2>
                        <p class="text-[12px] text-zinc-500">The first image is the cover. JPG, PNG or WebP, up to 5 MB each.</p>
                    </div>

                    @if ($editing && $product->images->isNotEmpty())
                        <div x-show="marked.length > 0" x-cloak>
                            <button type="submit"
                                    form="batch-delete-images-form"
                                    @click="confirmBatchDelete($event)"
                                    class="btn-danger h-9 px-3.5 text-xs font-semibold flex items-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                <span>Delete marked (<span x-text="marked.length"></span>)</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($editing && $product->images->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-5">
                        @foreach ($product->images as $img)
                            <div class="relative block rounded-xl border bg-zinc-50 aspect-square overflow-hidden group transition-all"
                                 :class="isMarked({{ $img->id }}) ? 'border-red-500 ring-2 ring-red-500/30 bg-red-50/40' : 'border-black/[0.08] hover:border-black/20'">
                                
                                <img src="{{ $img->url() }}" alt="" 
                                     class="w-full h-full object-contain p-2.5 transition-all"
                                     :class="isMarked({{ $img->id }}) ? 'opacity-30 grayscale filter' : ''">

                                <div x-show="isMarked({{ $img->id }})" x-cloak class="absolute inset-0 flex items-center justify-center pointer-events-none p-1 text-center">
                                    <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow">Marked to delete</span>
                                </div>

                                <button type="submit"
                                        form="del-img-{{ $img->id }}"
                                        title="Delete image now"
                                        class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-white/95 hover:bg-red-600 text-zinc-500 hover:text-white shadow-sm flex items-center justify-center text-xs transition opacity-80 group-hover:opacity-100 z-10"
                                        onclick="return confirm('Delete this image now?')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>

                                <label class="absolute inset-x-0 bottom-0 py-1.5 px-2 text-[11px] font-medium flex items-center justify-center gap-1.5 cursor-pointer select-none transition z-10"
                                       :class="isMarked({{ $img->id }}) ? 'bg-red-600 text-white' : 'bg-white/95 text-zinc-700 hover:bg-white'">
                                    <input type="checkbox"
                                           name="remove_images[]"
                                           value="{{ $img->id }}"
                                           :checked="isMarked({{ $img->id }})"
                                           @change="toggleMark({{ $img->id }})"
                                           class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                                    <span x-text="isMarked({{ $img->id }}) ? 'Marked' : 'Mark to delete'">Mark to delete</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="rounded-xl border border-dashed border-black/15 bg-zinc-50/60 p-4 transition hover:bg-zinc-50">
                    <input type="file"
                           id="product-images-input"
                           name="images[]"
                           multiple
                           accept="image/*"
                           class="hidden"
                           @change="onFilesSelected($event)">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click="document.getElementById('product-images-input').click()"
                                    class="btn-secondary h-10 px-4 text-[13px] font-medium inline-flex items-center gap-2 shrink-0 bg-white shadow-sm hover:border-black/30">
                                <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span>Choose images</span>
                            </button>

                            <div class="text-[13px] text-zinc-600 truncate">
                                <span x-show="filesCount === 0" class="text-zinc-400">No new files chosen</span>
                                <span x-show="filesCount > 0" x-cloak class="font-medium text-ink">
                                    <span x-text="filesCount"></span> <span x-text="filesCount === 1 ? 'image' : 'images'"></span> selected
                                </span>
                            </div>
                        </div>

                        <div x-show="filesCount > 0" x-cloak class="flex items-center gap-2 shrink-0">
                            @if ($editing)
                                <button type="submit"
                                        formaction="{{ route('admin.products.images.store', $product) }}"
                                        formenctype="multipart/form-data"
                                        formnovalidate
                                        class="btn-primary h-10 px-4 text-[13px] inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    <span>Upload now (<span x-text="filesCount"></span>)</span>
                                </button>
                            @endif
                            <button type="button"
                                    @click="clearSelectedFiles()"
                                    class="btn-secondary h-10 px-3 text-[12px] text-zinc-500 hover:text-red-600 bg-white">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div x-show="filesCount > 0" x-cloak class="mt-3 flex flex-wrap gap-1.5 pt-2 border-t border-black/[0.06]">
                        <template x-for="(name, idx) in fileNames" :key="idx">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white border border-black/[0.08] text-[11px] font-mono text-zinc-700 max-w-xs truncate shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="truncate" x-text="name"></span>
                            </span>
                        </template>
                    </div>
                </div>

                @error('images.*')<p class="text-[13px] text-red-600 mt-2">{{ $message }}</p>@enderror
                @error('images')<p class="text-[13px] text-red-600 mt-2">{{ $message }}</p>@enderror
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
                                      val = val.replace(/^\s*(?:\[?\d+[\.\)\:\-\]]+|[\*\-\•])\s+(https?:\/\/)/gmi, '$1')
                                               .replace(/([^\r\n\s])(https?:\/\/)/gi, '$1\n$2')
                                               .replace(/[ \t]+(https?:\/\/)/gi, '\n$1');
                                      const lines = val.split(/\r\n|\r|\n/).map(l => l.trim()).filter(Boolean);
                                      const keys = [];
                                      let current = '';
                                      for (const line of lines) {
                                          if (/^(?:\[?\d+[\.\)\:\-\]]+|[\*\-\•])$/u.test(line)) {
                                              continue;
                                          }
                                          if (/^https?:\/\//i.test(line)) {
                                              if (current) keys.push(current.replace(/(?:[\s\-_]*(?:\d+[\.\)\:\-]|\[\d+\]))$/u, ''));
                                              current = line;
                                          } else if (current) {
                                              current += line;
                                          }
                                      }
                                      if (current) keys.push(current.replace(/(?:[\s\-_]*(?:\d+[\.\)\:\-]|\[\d+\]))$/u, ''));
                                      return keys.filter(Boolean);
                                  }
                                  return val.split(/\r\n|\r|\n/).map(l => l.trim().replace(/^\s*(?:\[?\d+[\.\)\:\-\]]+|[\*\-\•])\s+/u, '')).filter(Boolean);
                              }
                         }">
                        @if ($editing)
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="font-medium text-emerald-700" id="pool-available-count">✓ {{ $product->availableDigitalKeysCount() }} links available in pool</span>
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
                                <summary class="cursor-pointer text-zinc-500 hover:text-ink">View links currently in pool (<span id="pool-total-count">{{ $product->digitalKeys->count() }}</span>)</summary>
                                <div class="mt-2 max-h-48 overflow-y-auto overflow-x-hidden rounded-lg border border-black/[0.06] bg-white divide-y divide-black/[0.04] min-w-0">
                                    @foreach ($product->digitalKeys as $key)
                                                                                <div id="key-row-{{ $key->id }}" class="p-2 flex items-center justify-between gap-2 text-[12px] min-w-0 transition-opacity">
                                            <span class="font-mono truncate select-all flex-1 min-w-0 {{ $key->isAssigned() ? 'text-zinc-400 line-through' : 'text-ink' }}" :title="'{{ addslashes($key->key_content) }}'">{{ $key->key_content }}</span>
                                            @if ($key->isAssigned())
                                                <span class="badge text-[10px] bg-zinc-100 text-zinc-500 shrink-0">Delivered</span>
                                            @else
                                                <button type="button"
                                                        id="btn-remove-key-{{ $key->id }}"
                                                        onclick="removePoolKey({{ $key->id }}, '{{ route('admin.products.keys.destroy', [$product, $key]) }}')"
                                                        class="text-red-500 hover:text-red-700 font-medium shrink-0 cursor-pointer px-1.5 py-0.5 rounded hover:bg-red-50 transition-colors">
                                                    Remove
                                                </button>
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
                {{-- Pricing & Ask for price --}}
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-50/50 p-3.5 space-y-1.5">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="ask_for_price" value="1" x-model="askForPrice" class="mt-0.5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-[13px] font-semibold text-emerald-950 block">Ask for price (Price on request)</span>
                            <span class="text-[12px] text-emerald-800/80 leading-snug block">No fixed price. Displays an "Ask for price" button linking customers directly to WhatsApp. You can switch back to a fixed price at any time.</span>
                        </div>
                    </label>
                </div>

                <div x-show="!askForPrice" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="price">Price (₦) <span x-show="variants.length > 0" x-cloak class="font-normal text-emerald-600 text-xs">(auto-set from plans)</span></label>
                        <input id="price" name="price" inputmode="decimal" data-money-input value="{{ old('price', $naira($product->price)) }}" :required="variants.length === 0 && !askForPrice" :placeholder="variants.length > 0 ? 'From lowest plan' : '450,000'" class="field h-11" x-init="formatMoneyInput($el)" @input="formatMoneyInput($el)" @keydown="handleMoneyKeydown($event)" @blur="handleMoneyBlur($event)">
                    </div>
                    <div x-show="type === 'physical' && variants.length === 0">
                        <label class="label" for="compare_at_price">Was (₦) <span class="font-normal text-zinc-400">optional</span></label>
                        <input id="compare_at_price" name="compare_at_price" inputmode="decimal" data-money-input value="{{ old('compare_at_price', $naira($product->compare_at_price)) }}" placeholder="500,000" class="field h-11" x-init="formatMoneyInput($el)" @input="formatMoneyInput($el)" @keydown="handleMoneyKeydown($event)" @blur="handleMoneyBlur($event)">
                    </div>
                </div>
                <input type="hidden" name="has_variants" :value="variants.length > 0 ? '1' : '0'">
                @error('price')<p class="text-[13px] text-red-600 -mt-3">{{ $message }}</p>@enderror
                @error('compare_at_price')<p class="text-[13px] text-red-600 -mt-3">{{ $message }}</p>@enderror
                <div x-show="type === 'physical' || type === 'service'">
                    <label class="label" for="stock">
                        <span x-text="type === 'service' ? 'Spots available' : 'Stock'"></span>
                        <span class="font-normal text-zinc-400" x-text="type === 'service' ? '(optional, e.g. 2 spots left)' : '(leave empty if not tracked)'"></span>
                    </label>
                    <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" :placeholder="type === 'service' ? 'e.g. 2' : ''" class="field h-11">
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

        <form id="batch-delete-images-form" method="POST" action="{{ route('admin.products.images.destroy-batch', $product) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        @foreach ($product->images as $img)
            <form id="del-img-{{ $img->id }}" method="POST" action="{{ route('admin.products.images.destroy', [$product, $img]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
    <script>
        function removePoolKey(id, url) {
            if (!confirm('Remove this link from the pool?')) {
                return;
            }
            const btn = document.getElementById('btn-remove-key-' + id);
            const row = document.getElementById('key-row-' + id);
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Removing...';
            }
            if (row) {
                row.style.opacity = '0.35';
                row.style.pointerEvents = 'none';
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || document.querySelector('input[name="_token"]')?.value;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ _method: 'DELETE' })
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.error || data.message || 'Failed to remove link');
                }
                return data;
            })
            .then(data => {
                if (row) {
                    row.remove();
                }
                const avail = document.getElementById('pool-available-count');
                if (avail && typeof data.available_count !== 'undefined') {
                    avail.textContent = '✓ ' + data.available_count + ' links available in pool';
                }
                const total = document.getElementById('pool-total-count');
                if (total && typeof data.total_count !== 'undefined') {
                    total.textContent = data.total_count;
                }
            })
            .catch(err => {
                if (row) {
                    row.style.opacity = '1';
                    row.style.pointerEvents = 'auto';
                }
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Remove';
                }
                const form = document.getElementById('del-key-' + id);
                if (form) {
                    form.submit();
                } else {
                    alert(err.message || 'Error removing link from pool');
                }
            });
        }
    </script>
</x-admin-layout>
