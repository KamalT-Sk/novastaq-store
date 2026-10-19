<x-admin-layout title="Enquiries & Leads">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">Enquiries & Leads</h1>
            <p class="text-[13px] text-zinc-500 mt-0.5">Project enquiries and messages submitted via the contact form on novastaq.com.</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">Total Enquiries</p>
            <p class="text-2xl font-bold tracking-tight text-ink mt-1">{{ number_format($totalCount) }}</p>
            <p class="text-[11px] text-zinc-500 mt-1">All messages received</p>
        </div>
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">New / Awaiting Reply</p>
            <p class="text-2xl font-bold tracking-tight text-emerald-600 mt-1">{{ number_format($newCount) }}</p>
            <p class="text-[11px] text-emerald-700 mt-1">Requires attention</p>
        </div>
        <div class="panel p-4">
            <p class="text-[12px] font-semibold text-zinc-400 uppercase tracking-wider">Replied</p>
            <p class="text-2xl font-bold tracking-tight text-indigo-600 mt-1">{{ number_format($repliedCount) }}</p>
            <p class="text-[11px] text-zinc-500 mt-1">Responses dispatched</p>
        </div>
    </div>

    {{-- Search & Tab filter --}}
    <div class="panel p-4 mb-6 space-y-3">
        <form method="GET" action="{{ route('admin.enquiries.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center text-zinc-400">🔍</span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search by name, email, company, service, or message content..." class="field h-10 pl-9 text-[13px] bg-white">
            </div>
            <button class="btn-secondary h-10 px-4 text-[13px]">Search</button>
            @if ($q !== '')
                <a href="{{ route('admin.enquiries.index', ['tab' => $tab]) }}" class="btn-secondary h-10 px-3 text-[13px] text-zinc-500">Clear</a>
            @endif
        </form>

        <div class="flex flex-wrap gap-2 pt-1 border-t border-black/[0.04]">
            <a href="{{ route('admin.enquiries.index', ['tab' => 'all', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'all' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                All ({{ $totalCount }})
            </a>
            <a href="{{ route('admin.enquiries.index', ['tab' => 'new', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'new' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                New ({{ $newCount }})
            </a>
            <a href="{{ route('admin.enquiries.index', ['tab' => 'replied', 'q' => $q]) }}" class="px-3 py-1.5 rounded-lg text-[13px] font-medium {{ $tab === 'replied' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-600 hover:text-ink' }}">
                Replied ({{ $repliedCount }})
            </a>
        </div>
    </div>

    {{-- Enquiries Table --}}
    <div class="panel overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-black/[0.06] text-[12px] font-semibold text-zinc-400 uppercase tracking-wider bg-zinc-50/50">
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Sender</th>
                    <th class="py-3 px-4">Service &amp; Budget</th>
                    <th class="py-3 px-4">Message Snippet</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/[0.04] text-[13px]">
                @forelse ($enquiries as $enquiry)
                    <tr class="hover:bg-zinc-50/70 transition-colors {{ $enquiry->isNew() ? 'bg-emerald-50/20' : '' }}">
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if ($enquiry->isNew())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> New
                                </span>
                            @elseif ($enquiry->isReplied())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                    ✓ Replied
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600">
                                    {{ ucfirst($enquiry->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-medium text-ink hover:underline block">
                                {{ $enquiry->name ?: 'Anonymous' }}
                            </a>
                            <span class="text-zinc-500 text-xs block">{{ $enquiry->email }}</span>
                            @if ($enquiry->company)
                                <span class="text-zinc-400 text-[11px] block">{{ $enquiry->company }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-medium text-ink block">{{ $enquiry->service ?: 'General' }}</span>
                            @if ($enquiry->budget && $enquiry->budget !== 'Not given')
                                <span class="text-zinc-500 text-xs block">{{ $enquiry->budget }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 max-w-xs">
                            <p class="truncate text-zinc-600 text-xs">{{ $enquiry->message }}</p>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-zinc-500 text-xs">
                            {{ $enquiry->created_at->format('M j, Y') }}
                            <span class="block text-zinc-400 text-[11px]">{{ $enquiry->created_at->format('g:i A') }}</span>
                        </td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-accent hover:underline">
                                View &amp; Reply →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-zinc-400 text-[13px]">
                            No enquiries found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($enquiries->hasPages())
        <div class="mt-6">
            {{ $enquiries->links() }}
        </div>
    @endif
</x-admin-layout>

