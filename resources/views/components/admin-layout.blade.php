@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Novastaq Admin</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-zinc-50 text-ink">
    @php($me = auth()->user())
    @php($links = array_filter([
        ['Dashboard', 'admin.dashboard', 'admin.dashboard', null],
        ['Orders', 'admin.orders.index', 'admin.orders.*', 'orders'],
        ['Customers', 'admin.customers.index', 'admin.customers.*', 'orders'],
        ['Emails', 'admin.emails.broadcast', 'admin.emails.*', 'orders'],
        ['Products', 'admin.products.index', 'admin.products.*', 'catalogue'],
        ['Categories', 'admin.categories.index', 'admin.categories.*', 'catalogue'],
        ['Discounts', 'admin.discounts.index', 'admin.discounts.*', 'catalogue'],
        ['Team', 'admin.team.index', 'admin.team.*', 'team'],
        ['Payments', 'admin.payments.index', 'admin.payments.*', 'system'],
    ], fn ($l) => $l[3] === null || $me->hasAccess($l[3])))
    <div x-data="{ open: false }" class="min-h-screen lg:flex">
        <aside class="lg:w-60 lg:min-h-screen bg-white border-b lg:border-b-0 lg:border-r border-black/[0.06] lg:sticky lg:top-0 lg:h-screen flex flex-col">
            <div class="h-16 px-5 flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="/logo.png" alt="Novastaq" class="h-5 w-auto">
                    <span class="text-[12px] font-semibold text-zinc-500 border-l border-black/10 pl-2">Admin</span>
                </a>
                <button @click="open = !open" class="lg:hidden w-9 h-9 rounded-lg hover:bg-zinc-100 inline-flex items-center justify-center" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
            <nav :class="open ? 'block' : 'hidden'" class="lg:block px-3 pb-4 space-y-0.5 flex-1">
                @foreach ($links as [$label, $route, $pattern])
                    <a href="{{ route($route) }}" class="block px-3 py-2 rounded-lg text-[14px] {{ request()->routeIs($pattern) ? 'bg-zinc-100 font-medium text-ink' : 'text-zinc-600 hover:bg-zinc-50 hover:text-ink' }}">{{ $label }}</a>
                @endforeach
                <div class="pt-4 mt-4 border-t border-black/[0.06] space-y-0.5">
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-[14px] text-zinc-600 hover:bg-zinc-50">View store ↗</a>
                    @if ($me->hasAccess('system'))
                        <form method="POST" action="{{ route('admin.system.update') }}" title="Run after each deploy: applies any database changes">
                            @csrf<button class="w-full text-left px-3 py-2 rounded-lg text-[14px] text-zinc-600 hover:bg-zinc-50">Apply updates</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full text-left px-3 py-2 rounded-lg text-[14px] text-zinc-600 hover:bg-zinc-50">Sign out</button></form>
                    <p class="px-3 pt-3 text-[12px] leading-snug text-zinc-400"><span class="text-zinc-600">{{ $me->name }}</span><br>{{ $me->roleLabel() }}</p>
                </div>
            </nav>
        </aside>

        <main class="flex-1 min-w-0 px-4 sm:px-8 py-8">
            @if (session('status') || session('error'))
                <div role="status" class="mb-6 rounded-xl px-4 py-3 text-[14px] {{ session('error') ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-white text-ink border border-black/[0.08]' }}">
                    {{ session('error') ?? session('status') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
    <script>
        function formatMoneyInput(el) {
            if (!el) return;
            const raw = el.value;
            if (!raw) return;

            const cursor = el.selectionStart ?? raw.length;
            const rawBeforeCursor = raw.slice(0, cursor);
            const charsBefore = (rawBeforeCursor.match(/[\d.]/g) || []).length;

            const clean = raw.replace(/[^\d.]/g, '');
            if (!clean) {
                el.value = '';
                return;
            }

            const parts = clean.split('.');
            let whole = parts[0];
            const frac = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '';

            if (whole.length > 1) {
                whole = whole.replace(/^0+/, '') || '0';
            }

            if (whole !== '') {
                whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            const formatted = whole + frac;
            el.value = formatted;

            let newCursor = 0;
            let charsSeen = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/[\d.]/.test(formatted[i])) charsSeen++;
                if (charsSeen >= charsBefore) {
                    newCursor = i + 1;
                    break;
                }
            }
            if (charsBefore === 0) {
                newCursor = 0;
            } else if (newCursor === 0) {
                newCursor = formatted.length;
            }

            el.setSelectionRange(newCursor, newCursor);
        }

        function handleMoneyKeydown(e) {
            const el = e.target;
            if (e.key === 'Backspace' && el.selectionStart === el.selectionEnd && el.selectionStart > 0) {
                if (el.value[el.selectionStart - 1] === ',') {
                    const pos = el.selectionStart;
                    el.setSelectionRange(pos - 1, pos - 1);
                }
            } else if (e.key === 'Delete' && el.selectionStart === el.selectionEnd && el.selectionStart < el.value.length) {
                if (el.value[el.selectionStart] === ',') {
                    const pos = el.selectionStart;
                    el.setSelectionRange(pos + 1, pos + 1);
                }
            }
        }

        function handleMoneyBlur(e) {
            const el = e.target;
            if (el.value && el.value.endsWith('.')) {
                el.value = el.value.slice(0, -1);
            }
        }

        document.addEventListener('input', function (e) {
            if (e.target && (e.target.matches('[data-money-input]') || e.target.id === 'price' || e.target.id === 'compare_at_price')) {
                formatMoneyInput(e.target);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.target && (e.target.matches('[data-money-input]') || e.target.id === 'price' || e.target.id === 'compare_at_price')) {
                handleMoneyKeydown(e);
            }
        });

        document.addEventListener('blur', function (e) {
            if (e.target && (e.target.matches('[data-money-input]') || e.target.id === 'price' || e.target.id === 'compare_at_price')) {
                handleMoneyBlur(e);
            }
        }, true);

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-money-input], #price, #compare_at_price').forEach(function (el) {
                formatMoneyInput(el);
            });
        });
    </script>
</body>
</html>
