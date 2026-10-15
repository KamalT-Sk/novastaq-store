<x-admin-layout title="Send Bulk Email">
    <div class="mb-6">
        <a href="{{ route('admin.customers.index') }}" class="text-[13px] text-zinc-500 hover:text-ink">← Customers & Users</a>
        <h1 class="text-2xl font-semibold tracking-[-0.02em] mt-1">Send Bulk Email</h1>
        <p class="text-[13px] text-zinc-500 mt-0.5">Broadcast an announcement, product update, or special offer to your customers.</p>
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start max-w-full">
        {{-- Compose form --}}
        <div class="space-y-6 min-w-0">
            <form id="broadcast-form" method="POST" action="{{ route('admin.customers.broadcast.send') }}"
                  onsubmit="return confirm('Are you sure you want to send this broadcast email to the selected audience? This cannot be undone.')"
                  class="panel p-6 space-y-5">
                @csrf

                <div>
                    <label class="label" for="audience">Target Audience</label>
                    <select id="audience" name="audience" required class="field h-11 bg-white">
                        <option value="all" @selected(old('audience') === 'all')>
                            All Customers & Buyers ({{ $audienceCounts['all'] }} {{ Str::plural('recipient', $audienceCounts['all']) }})
                        </option>
                        <option value="registered" @selected(old('audience') === 'registered')>
                            Registered Users Only ({{ $audienceCounts['registered'] }} {{ Str::plural('recipient', $audienceCounts['registered']) }})
                        </option>
                        <option value="paying" @selected(old('audience') === 'paying')>
                            Paying Customers Only ({{ $audienceCounts['paying'] }} {{ Str::plural('recipient', $audienceCounts['paying']) }})
                        </option>
                    </select>
                    @error('audience')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    <p class="text-[12px] text-zinc-400 mt-1.5">Each recipient receives their own individual email. Emails are 100% private.</p>
                </div>

                <div>
                    <label class="label" for="subject">Email Subject Line</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Exciting New AI Tools Available on Novastaq Store!" class="field h-11 bg-white">
                    @error('subject')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="eyebrow">Tag / Eyebrow <span class="font-normal text-zinc-400">(optional)</span></label>
                        <input id="eyebrow" name="eyebrow" value="{{ old('eyebrow', 'Special Announcement') }}" placeholder="e.g. Special Offer or New Arrival" class="field h-10 bg-white">
                    </div>
                    <div>
                        <label class="label" for="headline">Headline Title</label>
                        <input id="headline" name="headline" value="{{ old('headline') }}" required placeholder="e.g. Introducing Google Gemini & More" class="field h-10 bg-white">
                        @error('headline')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="label" for="content">Message Content</label>
                    <textarea id="content" name="content" rows="10" required placeholder="Write your message here. Line breaks and paragraphs will be cleanly formatted in the email..." class="field bg-white leading-relaxed text-[14px]">{{ old('content') }}</textarea>
                    @error('content')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="p-4 rounded-xl bg-zinc-50 border border-black/[0.06] space-y-3">
                    <p class="text-[12px] font-semibold text-zinc-600 uppercase tracking-wider">Call to Action Button (optional)</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs mb-1" for="button_label">Button Text</label>
                            <input id="button_label" name="button_label" value="{{ old('button_label', 'Shop Now ↗') }}" placeholder="e.g. Shop Now ↗" class="field h-10 bg-white text-[13px]">
                        </div>
                        <div>
                            <label class="label text-xs mb-1" for="button_url">Button Link URL</label>
                            <input id="button_url" name="button_url" value="{{ old('button_url', config('app.url')) }}" placeholder="https://store.novastaq.com" class="field h-10 bg-white text-[13px]">
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row gap-3 items-center justify-between">
                    {{-- Test button --}}
                    <button type="submit" formaction="{{ route('admin.customers.broadcast.test') }}" formnovalidate class="btn-secondary w-full sm:w-auto h-11 px-4 text-[13px] inline-flex items-center justify-center gap-1.5">
                        <span>🔍</span>
                        <span>Send test to {{ auth()->user()->email }}</span>
                    </button>

                    {{-- Send broadcast --}}
                    <button type="submit" class="btn-primary w-full sm:w-auto h-11 px-6 text-[14px] inline-flex items-center justify-center gap-2">
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
                    <span>Privacy & Delivery</span>
                </h2>
                <ul class="text-[13px] text-zinc-600 space-y-2 list-disc list-inside leading-relaxed">
                    <li><strong>Individual Delivery:</strong> Emails are sent one by one via Mailhive. Recipients never see other customers' addresses.</li>
                    <li><strong>Branded Layout:</strong> Automatically includes Novastaq logo, official styling, WhatsApp contact, and help footer.</li>
                    <li><strong>Send a Test First:</strong> Click <em>"Send test to my email"</em> to inspect the exact look in your inbox before broadcasting.</li>
                </ul>
            </section>

            <section class="panel p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink text-[14px] flex items-center gap-1.5">
                        <span>📜</span>
                        <span>Previous Broadcasts</span>
                    </h2>
                    <span class="text-xs text-zinc-400">({{ $history->count() }})</span>
                </div>

                @if ($history->isEmpty())
                    <p class="text-[13px] text-zinc-400">No broadcasts sent yet. Sent campaigns will be logged here.</p>
                @else
                    <div class="divide-y divide-black/[0.04] max-h-96 overflow-y-auto -mx-2 px-2">
                        @foreach ($history as $b)
                            <div class="py-3 text-[13px] space-y-1">
                                <p class="font-medium text-ink">{{ $b->subject }}</p>
                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-zinc-500">
                                    <span class="badge bg-zinc-100 text-zinc-600">{{ $b->audienceLabel() }}</span>
                                    <span>· {{ $b->recipients_count }} sent</span>
                                    <span>· {{ $b->created_at->format('j M Y, H:i') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>
