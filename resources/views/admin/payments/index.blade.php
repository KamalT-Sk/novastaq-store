<x-admin-layout title="Payment Gateways">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">Payment Gateways</h1>
            <p class="text-[14px] text-zinc-500 mt-1">Manage online payment gateways. Switch any gateway on or off at any time.</p>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        @foreach ($gateways as $gw)
            <div class="panel p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-lg font-semibold">{{ $gw['label'] }}</h2>
                            <span class="text-[11px] font-medium uppercase px-2 py-0.5 rounded {{ $gw['is_enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">
                                {{ $gw['is_enabled'] ? 'Active' : 'Disabled' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-[13px] text-zinc-600 mb-4">{{ $gw['description'] }}</p>

                    <div class="border-t border-black/[0.06] pt-4 mb-4 space-y-2 text-[12px]">
                        <div class="flex justify-between items-center text-zinc-500">
                            <span>Status in checkout</span>
                            <span class="font-medium {{ $gw['is_enabled'] ? 'text-emerald-700' : 'text-zinc-400' }}">
                                {{ $gw['is_enabled'] ? 'Visible to customers' : 'Hidden from customers' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-zinc-500">
                            <span>API credentials</span>
                            <span class="font-medium {{ $gw['is_configured'] ? 'text-emerald-700' : 'text-amber-600' }}">
                                {{ $gw['is_configured'] ? '✓ Configured in .env' : '⚠ Missing in .env' }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-zinc-50 rounded-lg p-3 text-[11px] space-y-1 mb-6">
                        <p class="font-medium text-zinc-600">Webhook URL:</p>
                        <p class="font-mono text-zinc-500 select-all break-all">{{ $gw['webhook_url'] }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.payments.toggle', $gw['name']) }}">
                    @csrf
                    @if ($gw['is_enabled'])
                        <button type="submit" class="btn-secondary w-full text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200">
                            Switch Off
                        </button>
                    @else
                        <button type="submit" class="btn-primary w-full bg-emerald-600 hover:bg-emerald-700">
                            Switch On
                        </button>
                    @endif
                </form>
            </div>
        @endforeach
    </div>
</x-admin-layout>
