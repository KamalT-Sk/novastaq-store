<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Novastaq Store</title>
    <meta name="description" content="Phones, laptops, gadgets and subscriptions from Novastaq Technologies Inc. Pay securely with Paystack.">
    <link rel="icon" type="image/png" href="/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-ink">
    <header x-data="{ open: false }" class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-black/[0.06]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0" aria-label="Novastaq Store home">
                <img src="/logo.png" alt="Novastaq" class="h-5 w-auto">
                <span class="text-[13px] font-semibold text-zinc-500 border-l border-black/10 pl-2">Store</span>
            </a>

            <nav class="hidden md:flex items-center gap-6 text-[14px] text-zinc-600 whitespace-nowrap">
                <a href="{{ route('shop') }}" class="hover:text-ink">All products</a>
                @foreach ($navCategories->take(3) as $navCategory)
                    <a href="{{ route('category', $navCategory) }}" class="hover:text-ink whitespace-nowrap">{{ $navCategory->name }}</a>
                @endforeach
            </nav>

            <form action="{{ route('shop') }}" class="hidden xl:block ml-auto w-56">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products" class="field h-9 text-[13px] rounded-full">
            </form>

            <div class="flex items-center gap-2 ml-auto xl:ml-0 whitespace-nowrap">
                <a href="{{ route('cart') }}" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-zinc-100" aria-label="Cart, {{ $cartCount }} items">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.39c.51 0 .96.34 1.09.84l.38 1.44m0 0L6.75 12h10.5l2.25-6.72H5.11ZM6.75 12 5.4 17.25h13.35M9 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm9 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                    @if ($cartCount)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-accent text-white text-[11px] font-semibold flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                </a>
                @auth
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-flex btn-secondary h-9 px-4">Admin</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex btn-primary h-9 px-4">My account</a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-primary h-9 px-4">Sign in</a>
                @endauth
                <button @click="open = !open" class="md:hidden w-10 h-10 inline-flex items-center justify-center rounded-full hover:bg-zinc-100" aria-label="Menu" :aria-expanded="open">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak class="md:hidden border-t border-black/[0.06] px-4 py-4 space-y-1 text-[15px]">
            <form action="{{ route('shop') }}" class="mb-3"><input type="search" name="q" placeholder="Search products" class="field h-10"></form>
            <a href="{{ route('shop') }}" class="block py-2">All products</a>
            @foreach ($navCategories as $navCategory)
                <a href="{{ route('category', $navCategory) }}" class="block py-2">{{ $navCategory->name }}</a>
            @endforeach
            @auth
                <a href="{{ route('dashboard') }}" class="block py-2 font-medium">My account</a>
                @if (auth()->user()->is_admin)<a href="{{ route('admin.dashboard') }}" class="block py-2 font-medium">Admin</a>@endif
            @else
                <a href="{{ route('login') }}" class="block py-2 font-medium">Sign in</a>
            @endauth
        </div>
    </header>

    @if (session('status') || session('error'))
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-6">
            <div role="status" class="rounded-xl px-4 py-3 text-[14px] {{ session('error') ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-zinc-50 text-ink border border-black/[0.06]' }}">
                {{ session('error') ?? session('status') }}
            </div>
        </div>
    @endif

    @isset($header)
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10">{{ $header }}</div>
    @endisset

    <main>{{ $slot }}</main>

    @php($footLink = 'text-[13px] text-zinc-500 hover:text-ink transition-colors')
    <footer class="mt-24 border-t border-black/[0.06]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-10">
            <div class="grid grid-cols-2 md:grid-cols-[1.6fr_1fr_1fr_1fr] gap-10 mb-14">
                <div class="col-span-2 md:col-span-1">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-4" aria-label="Novastaq Store home">
                        <img src="/logo.png" alt="Novastaq" class="h-5 w-auto">
                        <span class="text-[12px] font-semibold text-zinc-500 border-l border-black/10 pl-2">Store</span>
                    </a>
                    <p class="text-[13px] leading-relaxed text-zinc-500 max-w-xs mb-5">Genuine phones, laptops, gadgets and software subscriptions for you and your team, delivered to your door.</p>
                    <a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 h-9 px-4 rounded-full border border-black/10 text-[13px] font-medium hover:bg-zinc-50">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.69.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.31l-.34-.2-3.56.93.95-3.47-.22-.36a9.4 9.4 0 0 1-1.44-5.02c0-5.2 4.24-9.43 9.44-9.43 2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 0 1 2.76 6.67c0 5.2-4.23 9.43-9.45 9.43m8.03-17.46A11.27 11.27 0 0 0 12.05.72C5.79.72.7 5.8.7 12.06c0 2 .52 3.95 1.52 5.67L.6 23.28l5.68-1.49a11.3 11.3 0 0 0 5.77 1.47h.01c6.25 0 11.34-5.09 11.34-11.34 0-3.03-1.18-5.88-3.32-8.02"/></svg>
                        Chat with someone
                    </a>
                </div>

                <div>
                    <p class="text-[13px] font-medium mb-4">Shop</p>
                    <ul class="space-y-3">
                        <li><a href="{{ route('shop') }}" class="{{ $footLink }}">All products</a></li>
                        @foreach ($navCategories as $navCategory)
                            <li><a href="{{ route('category', $navCategory) }}" class="{{ $footLink }}">{{ $navCategory->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="text-[13px] font-medium mb-4">Help</p>
                    <ul class="space-y-3">
                        <li><a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="{{ $footLink }}">Bulk & business orders</a></li>
                        <li><a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="{{ $footLink }}">Delivery questions</a></li>
                        <li><a href="mailto:{{ config('store.support_email') }}" class="{{ $footLink }}">{{ config('store.support_email') }}</a></li>
                    </ul>
                </div>

                <div>
                    <p class="text-[13px] font-medium mb-4">Account</p>
                    <ul class="space-y-3">
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="{{ $footLink }}">My orders</a></li>
                            <li><a href="{{ route('profile.edit') }}" class="{{ $footLink }}">Profile</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="{{ $footLink }}">Sign in</a></li>
                            <li><a href="{{ route('register') }}" class="{{ $footLink }}">Create account</a></li>
                        @endauth
                        <li><a href="{{ route('cart') }}" class="{{ $footLink }}">Cart</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-black/[0.06] flex flex-col md:flex-row md:items-center justify-between gap-4 text-[13px] text-zinc-400">
                <p>© {{ date('Y') }} Novastaq Technologies Inc. All rights reserved.</p>
                <div class="flex flex-wrap items-center gap-5">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        Payments secured by Paystack
                    </span>
                    <a href="{{ config('store.main_site') }}" class="hover:text-ink">novastaq.com ↗</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
