<x-admin-layout :title="'Enquiry: ' . $enquiry->subject">
    <div class="mb-6">
        <a href="{{ route('admin.enquiries.index') }}" class="text-[13px] text-zinc-500 hover:text-ink font-medium">← Back to Enquiries</a>
        <div class="flex flex-wrap items-center justify-between gap-4 mt-2">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ $enquiry->subject }}</h1>
                @if ($enquiry->isNew())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> New
                    </span>
                @elseif ($enquiry->isReplied())
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                        ✓ Replied
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600">
                        {{ ucfirst($enquiry->status) }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}" onsubmit="return confirm('Are you sure you want to delete this enquiry?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1.5 rounded-lg border border-red-200 transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        <div class="space-y-6">
            {{-- Client Message Card --}}
            <section class="panel p-6">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-black/[0.06]">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400">Message from Client</h2>
                        <p class="text-xs text-zinc-500 mt-0.5">Submitted via novastaq.com contact form on {{ $enquiry->created_at->format('M j, Y \a\t g:i A') }}</p>
                    </div>
                </div>

                <div class="bg-zinc-50 border border-black/[0.06] rounded-xl p-5 text-[14px] leading-relaxed text-ink whitespace-pre-wrap selection:bg-indigo-100">
                    {{ $enquiry->message }}
                </div>

                {{-- Submission Metadata/Fields --}}
                @if (!empty($enquiry->fields) && is_array($enquiry->fields))
                    <div class="mt-5 pt-4 border-t border-black/[0.06]">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">Form Breakdown</h3>
                        <div class="grid sm:grid-cols-2 gap-3 text-[13px]">
                            @foreach ($enquiry->fields as $label => $val)
                                @if (!in_array(strtolower($label), ['message', 'website']))
                                    <div class="p-3 bg-zinc-50/70 border border-black/[0.04] rounded-lg">
                                        <span class="text-zinc-400 text-xs block">{{ $label }}</span>
                                        <span class="font-medium text-ink block mt-0.5">{{ (string) $val }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- Reply History (if already sent) --}}
            @if ($enquiry->isReplied() && $enquiry->reply_message)
                <section class="panel p-6 bg-indigo-50/30 border-indigo-200/80">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-indigo-100">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white text-xs">✓</span>
                            <div>
                                <h3 class="text-sm font-semibold text-indigo-950">Response Sent to Customer</h3>
                                <p class="text-xs text-indigo-700">
                                    Dispatched {{ $enquiry->replied_at?->diffForHumans() }} ({{ $enquiry->replied_at?->format('M j, Y \a\t g:i A') }})
                                    @if ($enquiry->repliedBy)
                                        by <span class="font-medium">{{ $enquiry->repliedBy->name }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @if ($enquiry->reply_subject)
                            <div class="text-xs text-zinc-600">
                                <span class="text-zinc-400 font-medium">Subject:</span> {{ $enquiry->reply_subject }}
                            </div>
                        @endif
                        <div class="bg-white border border-indigo-100 rounded-xl p-4 text-[14px] leading-relaxed text-zinc-800 whitespace-pre-wrap">
                            {{ $enquiry->reply_message }}
                        </div>
                    </div>
                </section>
            @endif

            {{-- Reply Box --}}
            <section class="panel p-6">
                <div class="pb-4 mb-4 border-b border-black/[0.06]">
                    <h2 class="text-base font-semibold text-ink">
                        {{ $enquiry->isReplied() ? 'Send Another Response' : 'Reply Directly to Customer' }}
                    </h2>
                    <p class="text-xs text-zinc-500 mt-1">
                        Your message will be sent to <span class="font-semibold text-ink">{{ $enquiry->email }}</span> using the branded Novastaq template.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.enquiries.reply', $enquiry) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="subject" class="label text-xs">Email Subject Line</label>
                        <input type="text" id="subject" name="subject"
                               value="{{ old('subject', str_starts_with($enquiry->subject, 'Re:') ? $enquiry->subject : 'Re: ' . $enquiry->subject) }}"
                               class="field h-10 bg-white text-[13px]" required>
                    </div>

                    <div>
                        <label for="message" class="label text-xs">Response Message</label>
                        <textarea id="message" name="message" rows="7" required
                                  placeholder="Hi {{ $enquiry->name ?: 'there' }},&#10;&#10;Thank you for reaching out to Novastaq. We've reviewed your request..."
                                  class="field bg-white text-[13px] py-2.5 leading-relaxed">{{ old('message') }}</textarea>
                        <p class="text-[11px] text-zinc-400 mt-1">Line breaks and formatting will be preserved in the sent email.</p>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-xs text-zinc-500">
                            Sent from <span class="font-semibold text-ink">Novastaq &lt;{{ config('store.support_email', 'hello@novastaq.com') }}&gt;</span>
                        </span>
                        <button type="submit" class="btn-primary h-10 px-5 text-[13px] inline-flex items-center gap-1.5">
                            <span>✉</span>
                            <span>{{ $enquiry->isReplied() ? 'Send Follow-up Email' : 'Send Response to Customer' }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Sidebar: Client Details & Status --}}
        <div class="space-y-6">
            {{-- Contact Information --}}
            <section class="panel p-5 space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Contact Details</h3>

                <div class="space-y-3 text-[13px]">
                    <div>
                        <span class="text-zinc-400 text-xs block">Full Name</span>
                        <span class="font-medium text-ink block text-sm">{{ $enquiry->name ?: 'Not provided' }}</span>
                    </div>

                    <div>
                        <span class="text-zinc-400 text-xs block">Email Address</span>
                        <a href="mailto:{{ $enquiry->email }}" class="font-medium text-accent hover:underline block break-all">
                            {{ $enquiry->email }}
                        </a>
                    </div>

                    @if ($enquiry->phone)
                        <div>
                            <span class="text-zinc-400 text-xs block">Phone / WhatsApp</span>
                            <span class="font-medium text-ink block">{{ $enquiry->phone }}</span>
                            @if ($enquiry->whatsappUrl())
                                <a href="{{ $enquiry->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-emerald-700 font-semibold hover:underline mt-1">
                                    <span>💬 Chat on WhatsApp ↗</span>
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($enquiry->company)
                        <div>
                            <span class="text-zinc-400 text-xs block">Company</span>
                            <span class="font-medium text-ink block">{{ $enquiry->company }}</span>
                        </div>
                    @endif

                    @if ($enquiry->service)
                        <div>
                            <span class="text-zinc-400 text-xs block">Requested Service</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-100 text-ink mt-0.5">
                                {{ $enquiry->service }}
                            </span>
                        </div>
                    @endif

                    @if ($enquiry->budget && $enquiry->budget !== 'Not given')
                        <div>
                            <span class="text-zinc-400 text-xs block">Estimated Budget</span>
                            <span class="font-medium text-ink block">{{ $enquiry->budget }}</span>
                        </div>
                    @endif

                    <div class="pt-2 border-t border-black/[0.04] text-xs text-zinc-400 space-y-1">
                        <div>Received: {{ $enquiry->created_at->format('M j, Y · g:i A') }}</div>
                        @if ($enquiry->ip_address)
                            <div>IP: {{ $enquiry->ip_address }}</div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Status Management --}}
            <section class="panel p-5 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Change Status</h3>

                <form method="POST" action="{{ route('admin.enquiries.status', $enquiry) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="field h-10 text-[13px] bg-white">
                        <option value="new" @selected($enquiry->status === 'new')>Mark as New (Unanswered)</option>
                        <option value="replied" @selected($enquiry->status === 'replied')>Mark as Replied</option>
                        <option value="archived" @selected($enquiry->status === 'archived')>Mark as Archived</option>
                    </select>
                    <button type="submit" class="btn-secondary w-full h-9 text-xs">Update Status</button>
                </form>
            </section>
        </div>
    </div>
</x-admin-layout>

