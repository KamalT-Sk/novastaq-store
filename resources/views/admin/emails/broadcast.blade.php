<x-admin-layout title="Email Broadcasts & Campaigns">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">Email Broadcasts & Campaigns</h1>
            <p class="text-[13px] text-zinc-500 mt-0.5">Send marketing emails, product drops, discount announcements, and newsletters to your store audience.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge {{ $isConfigured ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' }} border text-xs px-2.5 py-1">
                {{ $driverLabel }}
            </span>
            <a href="{{ route('admin.emails.settings') }}" class="btn-secondary h-9 px-3 text-xs inline-flex items-center gap-1">
                <span>⚙</span>
                <span>Settings</span>
            </a>
        </div>
    </div>

    @include('admin.emails.tabs', ['current' => 'broadcast'])

    @if (! $isConfigured)
        <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 text-[13px]">
                <span class="text-lg">⚠️</span>
                <span><strong>Mail provider is not configured.</strong> Test emails and customer broadcasts will fail until credentials are provided.</span>
            </div>
            <a href="{{ route('admin.emails.settings') }}" class="btn-primary text-xs h-8 px-3 whitespace-nowrap bg-amber-900 hover:bg-amber-950">
                Configure Mail Provider →
            </a>
        </div>
    @endif

    {{-- Banner when editing a previous broadcast --}}
    @if ($loadedBroadcast)
        <div class="mb-6 p-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-[13px]">
                <p class="font-semibold">
                    Loaded from {{ $loadedBroadcast->isDraft() ? 'Draft' : 'Previous Broadcast' }} #{{ $loadedBroadcast->id }} ({{ $loadedBroadcast->created_at->format('M j, Y') }})
                </p>
                <p class="text-sky-700 text-xs mt-0.5">You can modify the text, adjust audience, test, and resend or save as a new campaign.</p>
            </div>
            <a href="{{ route('admin.emails.broadcast') }}" class="text-xs text-sky-800 font-medium underline hover:text-sky-950 whitespace-nowrap">
                Clear &amp; Start Fresh ✕
            </a>
        </div>
    @endif

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start max-w-full" x-data="{ previewModalOpen: false, previewUrl: '' }">
        {{-- Compose form --}}
        <div class="space-y-6 min-w-0">
            <form id="broadcast-form" method="POST" action="{{ route('admin.emails.broadcast.send') }}"
                  class="panel p-6 space-y-5">
                @csrf

                @if ($loadedBroadcast)
                    <input type="hidden" name="broadcast_id" value="{{ $loadedBroadcast->id }}">
                @endif

                <div>
                    <label class="label" for="audience">Target Audience</label>
                    <select id="audience" name="audience" required class="field h-11 bg-white">
                        <option value="all" @selected(old('audience', $loadedBroadcast?->audience) === 'all')>
                            All Customers &amp; Buyers ({{ $audienceCounts['all'] }} {{ Str::plural('recipient', $audienceCounts['all']) }})
                        </option>
                        <option value="registered" @selected(old('audience', $loadedBroadcast?->audience) === 'registered')>
                            Registered Users Only ({{ $audienceCounts['registered'] }} {{ Str::plural('recipient', $audienceCounts['registered']) }})
                        </option>
                        <option value="paying" @selected(old('audience', $loadedBroadcast?->audience) === 'paying')>
                            Paying Customers Only ({{ $audienceCounts['paying'] }} {{ Str::plural('recipient', $audienceCounts['paying']) }})
                        </option>
                    </select>
                    @error('audience')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    <p class="text-[12px] text-zinc-400 mt-1.5">Each recipient receives their own individual email. Emails are 100% private.</p>
                </div>

                <div>
                    <label class="label" for="subject">Email Subject Line</label>
                    <input id="subject" name="subject"
                           value="{{ old('subject', $loadedBroadcast?->subject) }}"
                           required placeholder="e.g. Exciting New AI Tools Available on Novastaq Store!"
                           class="field h-11 bg-white">
                    @error('subject')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="eyebrow">Tag / Eyebrow <span class="font-normal text-zinc-400">(optional)</span></label>
                        <input id="eyebrow" name="eyebrow"
                               value="{{ old('eyebrow', $loadedBroadcast?->eyebrow ?? 'Special Announcement') }}"
                               placeholder="e.g. Special Offer or New Arrival"
                               class="field h-10 bg-white">
                    </div>
                    <div>
                        <label class="label" for="headline">Headline Title</label>
                        <input id="headline" name="headline"
                               value="{{ old('headline', $loadedBroadcast?->headline) }}"
                               required placeholder="e.g. Introducing Google Gemini & More"
                               class="field h-10 bg-white">
                        @error('headline')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="label" for="content">Message Content</label>
                    <textarea id="content" name="content" rows="10" required
                              placeholder="Write your message here. Line breaks and paragraphs will be cleanly formatted in the email..."
                              class="field bg-white leading-relaxed text-[14px]">{{ old('content', $loadedBroadcast?->content) }}</textarea>
                    @error('content')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="p-4 rounded-xl bg-zinc-50 border border-black/[0.06] space-y-4"
                     x-data="{
                         setDestination(url, label) {
                             const urlInput = document.getElementById('button_url');
                             const labelInput = document.getElementById('button_label');
                             if (url) {
                                 urlInput.value = url;
                             }
                             if (label) {
                                 labelInput.value = label;
                             }
                         }
                     }">
                    <div class="flex items-center justify-between">
                        <p class="text-[12px] font-semibold text-zinc-700 uppercase tracking-wider">Call to Action Button (optional)</p>
                        <span class="text-[11px] text-zinc-400">Link to store or any specific product</span>
                    </div>

                    {{-- Quick Destination Helper --}}
                    <div class="p-3 bg-white rounded-lg border border-black/[0.06] space-y-2">
                        <span class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider block">Quick Link Destination</span>
                        <div class="grid sm:grid-cols-2 gap-2.5 items-center">
                            {{-- Store Presets --}}
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="setDestination('{{ url('/') }}', 'Visit Store ↗')"
                                        class="btn-secondary h-8 px-2.5 text-xs inline-flex items-center gap-1 hover:bg-zinc-100">
                                    <span>🏬</span>
                                    <span>Store Home</span>
                                </button>
                                <button type="button" @click="setDestination('{{ url('/shop') }}', 'Shop Catalogue ↗')"
                                        class="btn-secondary h-8 px-2.5 text-xs inline-flex items-center gap-1 hover:bg-zinc-100">
                                    <span>🛍️</span>
                                    <span>All Products</span>
                                </button>
                            </div>

                            {{-- Specific Product Dropdown --}}
                            <div>
                                <select id="product_quick_select"
                                        @change="if ($event.target.value) { 
                                            const opt = $event.target.options[$event.target.selectedIndex];
                                            setDestination(opt.value, 'Get ' + opt.dataset.name + ' ↗');
                                        }"
                                        class="field h-8 text-xs bg-white">
                                    <option value="">🎯 Or pick a specific product...</option>
                                    @foreach ($products as $p)
                                        <option value="{{ url('/p/' . $p->slug) }}" data-name="{{ $p->name }}">
                                            {{ $p->name }} · ₦{{ number_format($p->price) }} ({{ ucfirst($p->type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs mb-1" for="button_label">Button Text</label>
                            <input id="button_label" name="button_label"
                                   value="{{ old('button_label', $loadedBroadcast?->button_label ?? 'Shop Now ↗') }}"
                                   placeholder="e.g. Shop Now ↗ or Get Gemini Pro ↗"
                                   class="field h-10 bg-white text-[13px]">
                            @error('button_label')<p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label text-xs mb-1" for="button_url">Button Link URL</label>
                            <input id="button_url" name="button_url"
                                   value="{{ old('button_url', $loadedBroadcast?->button_url ?? config('app.url')) }}"
                                   placeholder="https://store.novastaq.com/p/..."
                                   class="field h-10 bg-white text-[13px] font-mono">
                            @error('button_url')<p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Test recipient field --}}
                <div class="p-3.5 rounded-xl bg-zinc-50/70 border border-black/[0.06] flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                    <div class="flex-1">
                        <label class="label text-xs mb-1" for="test_recipient">Test Recipient Email</label>
                        <input id="test_recipient" name="test_recipient" type="email"
                               value="{{ old('test_recipient', $adminEmail) }}"
                               placeholder="e.g. novastaqtechnologies@gmail.com"
                               class="field h-9 bg-white text-xs">
                    </div>
                    <div class="sm:pt-5">
                        <button type="submit" formaction="{{ route('admin.emails.broadcast.test') }}" formnovalidate
                                class="btn-secondary w-full sm:w-auto h-9 px-3.5 text-xs inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <span>🔍</span>
                            <span>Send Test Preview</span>
                        </button>
                    </div>
                </div>

                {{-- Action bar --}}
                <div class="pt-2 flex flex-col sm:flex-row gap-3 items-center justify-between border-t border-black/[0.06]">
                    {{-- Save as draft button --}}
                    <button type="submit" name="save_draft" value="1"
                            class="btn-secondary w-full sm:w-auto h-11 px-5 text-[13px] inline-flex items-center justify-center gap-1.5">
                        <span>📝</span>
                        <span>Save as Draft</span>
                    </button>

                    {{-- Send broadcast --}}
                    <button type="submit" name="action" value="send"
                            onclick="return confirm('Are you sure you want to send this broadcast email to the selected audience? This cannot be undone.')"
                            class="btn-primary w-full sm:w-auto h-11 px-6 text-[14px] inline-flex items-center justify-center gap-2">
                        <span>✉</span>
                        <span>Send Broadcast to Customers</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar: Tips & History --}}
        <div class="space-y-6 min-w-0">
            <section class="panel p-5 space-y-3">
                <h2 class="font-semibold text-ink text-[14px] flex items-center gap-1.5">
                    <span>🛡️</span>
                    <span>Privacy &amp; Delivery</span>
                </h2>
                <ul class="text-[13px] text-zinc-600 space-y-2 list-disc list-inside leading-relaxed">
                    <li><strong>Individual Delivery:</strong> Emails are sent one by one via {{ $driverLabel }}. Customer addresses are never exposed.</li>
                    <li><strong>Branded Layout:</strong> Automatically includes Novastaq logo, official styling, WhatsApp contact, and help footer.</li>
                    <li><strong>Resend Anytime:</strong> Any past broadcast can be edited and resent to your audience with 1 click below.</li>
                </ul>
            </section>

            <section class="panel p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink text-[14px] flex items-center gap-1.5">
                        <span>📜</span>
                        <span>Past Broadcasts &amp; Drafts</span>
                    </h2>
                    <span class="text-xs text-zinc-400">({{ $history->total() }})</span>
                </div>

                @if ($history->isEmpty())
                    <p class="text-[13px] text-zinc-400">No broadcasts sent yet. Campaigns and drafts will be logged here.</p>
                @else
                    <div class="divide-y divide-black/[0.06] -mx-2 px-2 max-h-[550px] overflow-y-auto space-y-3">
                        @foreach ($history as $b)
                            <div class="pt-3 first:pt-0 text-[13px] space-y-1.5">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-medium text-ink leading-snug">{{ $b->subject }}</p>
                                    @if ($b->isDraft())
                                        <span class="badge bg-amber-100 text-amber-800 text-[10px] uppercase font-semibold">Draft</span>
                                    @else
                                        <span class="badge bg-emerald-100 text-emerald-800 text-[10px] uppercase font-semibold">Sent</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-zinc-500">
                                    <span class="badge bg-zinc-100 text-zinc-600">{{ $b->audienceLabel() }}</span>
                                    @if (! $b->isDraft())
                                        <span>· {{ $b->recipients_count }} {{ Str::plural('recipient', $b->recipients_count) }}</span>
                                    @endif
                                    <span>· {{ $b->created_at->format('j M Y, H:i') }}</span>
                                </div>

                                {{-- Action Buttons: Edit & Resend, Preview, Delete --}}
                                <div class="pt-1 flex items-center gap-2 text-xs">
                                    <a href="{{ route('admin.emails.broadcast', ['clone' => $b->id]) }}"
                                       class="text-ink font-semibold hover:underline inline-flex items-center gap-1 bg-zinc-100 hover:bg-zinc-200 px-2.5 py-1 rounded-md">
                                        <span>🔁</span>
                                        <span>{{ $b->isDraft() ? 'Edit & Send' : 'Edit & Resend' }}</span>
                                    </a>

                                    <button type="button"
                                            @click="previewUrl = '{{ route('admin.emails.broadcast.preview', $b) }}'; previewModalOpen = true"
                                            class="text-zinc-600 hover:text-ink hover:underline inline-flex items-center gap-1 px-2 py-1">
                                        <span>👁</span>
                                        <span>Preview</span>
                                    </button>

                                    <form method="POST" action="{{ route('admin.emails.broadcast.destroy', $b) }}"
                                          onsubmit="return confirm('Delete this broadcast record from history?')"
                                          class="inline-block ml-auto">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs px-1">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($history->hasPages())
                        <div class="pt-3 border-t border-black/[0.06]">
                            {{ $history->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>

        {{-- Preview Modal --}}
        <div x-show="previewModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             @keydown.escape.window="previewModalOpen = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden"
                 @click.outside="previewModalOpen = false">
                <div class="px-5 py-4 border-b border-black/[0.08] flex items-center justify-between">
                    <h3 class="font-semibold text-sm">Email Preview</h3>
                    <button type="button" @click="previewModalOpen = false" class="text-zinc-400 hover:text-ink text-sm">✕ Close</button>
                </div>
                <div class="flex-1 overflow-auto bg-zinc-100 p-4">
                    <iframe :src="previewUrl" class="w-full h-[650px] bg-white rounded-xl shadow-inner border border-black/[0.06]"></iframe>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

