<x-admin-layout title="Discount Codes">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">Discount Codes</h1>
            <p class="text-[13px] text-zinc-500 mt-0.5">Offer percentage reductions on specific products (e.g. Gemini) or store-wide.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-black/[0.08] text-[13px] font-medium text-ink shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ $stats['active'] }} Active {{ Str::plural('Code', $stats['active']) }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-black/[0.08] text-[13px] text-zinc-600 shadow-xs">
                {{ $stats['total_uses'] }} {{ Str::plural('Redemption', $stats['total_uses']) }}
            </span>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        {{-- List of Discount Codes --}}
        <div class="space-y-4">
            <div class="panel p-4 flex flex-wrap items-center justify-between gap-3 bg-white">
                <form method="GET" action="{{ route('admin.discounts.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                    <input type="text" name="q" value="{{ $q }}" placeholder="Search code (e.g. GEMINI)..." class="field h-9 text-[13px] w-48 bg-zinc-50">
                    <select name="status" class="field h-9 text-[13px] w-32 bg-zinc-50" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    </select>
                    <select name="product_id" class="field h-9 text-[13px] max-w-44 bg-zinc-50" onchange="this.form.submit()">
                        <option value="">All Products</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" @selected($productId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary h-9 px-3 text-[13px]">Filter</button>
                    @if ($q || $status || $productId)
                        <a href="{{ route('admin.discounts.index') }}" class="text-[13px] text-zinc-500 hover:text-ink underline ml-1">Reset</a>
                    @endif
                </form>
            </div>

            <section class="panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[14px]">
                        <thead>
                            <tr class="border-b border-black/[0.06] bg-zinc-50/50 text-zinc-500 text-[12px] uppercase tracking-wider font-semibold">
                                <th class="py-3 px-4">Code</th>
                                <th class="py-3 px-4">Reduction</th>
                                <th class="py-3 px-4">Applies To</th>
                                <th class="py-3 px-4">Redemptions</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/[0.04]">
                            @forelse ($discounts as $d)
                                <tr class="hover:bg-zinc-50/60 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-ink">
                                        <span class="px-2 py-1 rounded bg-zinc-100 border border-black/[0.06]">{{ $d->code }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[13px]">
                                            {{ $d->percent_off }}% OFF
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-[13px]">
                                        @if ($d->product)
                                            <a href="{{ route('admin.products.edit', $d->product) }}" class="font-medium text-ink hover:underline">
                                                {{ $d->product->name }}
                                            </a>
                                            <div class="text-[11px] text-zinc-400">
                                                Regular: @naira($d->product->price) → Discounted: @naira(max(0, $d->product->price - $d->calculateDiscount($d->product->price)))
                                            </div>
                                        @else
                                            <span class="text-zinc-600 font-medium">All products (Store-wide)</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-[13px] text-zinc-600">
                                        {{ $d->uses_count }} used
                                        @if ($d->max_uses)
                                            <span class="text-zinc-400">/ {{ $d->max_uses }} max</span>
                                        @else
                                            <span class="text-zinc-400">(unlimited)</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-[13px]">
                                        @if ($d->is_active)
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-medium text-[12px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-zinc-400 text-[12px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('admin.discounts.toggle', $d) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-xs text-zinc-600 hover:text-ink underline">
                                                    {{ $d->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <span class="text-zinc-300">|</span>
                                            <form method="POST" action="{{ route('admin.discounts.destroy', $d) }}" onsubmit="return confirm('Delete discount code {{ $d->code }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-700">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-zinc-500 text-[14px]">
                                        No discount codes found. Create one using the form on the right.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($discounts->hasPages())
                    <div class="p-4 border-t border-black/[0.06]">
                        {{ $discounts->links() }}
                    </div>
                @endif
            </section>
        </div>

        {{-- Create Discount Code Panel --}}
        <section class="panel p-6 space-y-5 lg:sticky lg:top-8">
            <div>
                <h2 class="font-semibold text-lg">Create Discount Code</h2>
                <p class="text-[13px] text-zinc-500 mt-0.5">Generate a percentage coupon code for customers.</p>
            </div>

            <form method="POST" action="{{ route('admin.discounts.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label" for="code">Promo Code</label>
                    <input id="code" name="code" value="{{ old('code') }}" required maxlength="30" placeholder="e.g. GEMINI25 or NOVASALE" class="field h-11 uppercase font-mono tracking-wider">
                    @error('code')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="percent_off">Discount Reduction (%)</label>
                    <div class="relative">
                        <input id="percent_off" name="percent_off" type="number" min="1" max="100" value="{{ old('percent_off', 25) }}" required placeholder="25" class="field h-11 pr-8">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 font-medium">%</span>
                    </div>
                    <p class="text-[12px] text-zinc-400 mt-1">e.g. 25% turns a ₦10,000 item into ₦7,500.</p>
                    @error('percent_off')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="product_id">Applies to</label>
                    <select id="product_id" name="product_id" class="field h-11">
                        <option value="">All Products (Store-wide)</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }} (@naira($p->price))</option>
                        @endforeach
                    </select>
                    @error('product_id')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="max_uses">Max Redemptions</label>
                        <input id="max_uses" name="max_uses" type="number" min="1" value="{{ old('max_uses') }}" placeholder="Unlimited" class="field h-11">
                        @error('max_uses')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="expires_at">Expiry Date</label>
                        <input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}" class="field h-11 text-[13px]">
                        @error('expires_at')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 text-[14px] pt-1">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-black/20 text-ink focus:ring-ink">
                    Active immediately
                </label>

                <button class="btn-primary w-full h-11">Create discount code</button>
            </form>
        </section>
    </div>
</x-admin-layout>

