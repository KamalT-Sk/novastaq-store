@props(['current' => 'broadcast'])

<div class="flex items-center gap-2 border-b border-black/[0.08] mb-6 overflow-x-auto pb-px">
    <a href="{{ route('admin.emails.broadcast') }}"
       class="px-4 py-2 text-[13px] font-medium border-b-2 -mb-px transition-colors whitespace-nowrap {{ $current === 'broadcast' ? 'border-ink text-ink font-semibold' : 'border-transparent text-zinc-500 hover:text-ink' }}">
        ✉ Broadcast & Campaigns
    </a>
    <a href="{{ route('admin.emails.settings') }}"
       class="px-4 py-2 text-[13px] font-medium border-b-2 -mb-px transition-colors whitespace-nowrap {{ $current === 'settings' ? 'border-ink text-ink font-semibold' : 'border-transparent text-zinc-500 hover:text-ink' }}">
        ⚙ Mail Settings & Server
    </a>
    <a href="{{ route('admin.emails.automated') }}"
       class="px-4 py-2 text-[13px] font-medium border-b-2 -mb-px transition-colors whitespace-nowrap {{ $current === 'automated' ? 'border-ink text-ink font-semibold' : 'border-transparent text-zinc-500 hover:text-ink' }}">
        ⚡ Automated Store Emails
    </a>
</div>

