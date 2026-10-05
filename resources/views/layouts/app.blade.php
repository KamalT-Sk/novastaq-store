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

    <footer class="mt-24 border-t border-black/[0.06]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col md:flex-row gap-6 md:items-center justify-between text-[13px] text-zinc-500">
            <div class="flex items-center gap-3">
                <img src="/logo.png" alt="Novastaq" class="h-4 w-auto">
                <span>© {{ date('Y') }} Novastaq Technologies Inc.</span>
            </div>
            <div class="flex flex-wrap gap-5">
                <a href="{{ config('store.main_site') }}" class="hover:text-ink">novastaq.com</a>
                <a href="mailto:{{ config('store.support_email') }}" class="hover:text-ink">{{ config('store.support_email') }}</a>
                <a href="{{ config('store.whatsapp_url') }}" target="_blank" rel="noopener" class="hover:text-ink">Chat on WhatsApp</a>
                <span>Payments secured by Paystack</span>
            </div>
        </div>
    </footer>
</body>
</html>
