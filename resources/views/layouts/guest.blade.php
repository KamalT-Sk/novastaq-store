<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Account · Novastaq Store</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-ink bg-zinc-50">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('home') }}" class="flex items-center gap-2 mb-8" aria-label="Novastaq Store home">
            <img src="/logo.png" alt="Novastaq" class="h-6 w-auto">
            <span class="text-[13px] font-semibold text-zinc-500 border-l border-black/10 pl-2">Store</span>
        </a>
        <div class="w-full sm:max-w-md panel p-8">
            {{ $slot }}
        </div>
        <a href="{{ route('home') }}" class="mt-6 text-[13px] text-zinc-500 hover:text-ink">← Back to the store</a>
    </div>
</body>
</html>
