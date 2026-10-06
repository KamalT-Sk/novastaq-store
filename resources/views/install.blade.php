<x-guest-layout>
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-1">Set up the store</h1>
    <p class="text-[14px] text-zinc-500 mb-6">This creates the database tables and your admin account. It only runs once.</p>

    @if (session('error'))
        <div class="mb-5 rounded-xl px-4 py-3 text-[13px] bg-red-50 text-red-700 border border-red-100 break-words">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('install.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="name" class="label">Your name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field h-11">
            @error('name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="label">Admin email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="field h-11">
            @error('email')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="label">Admin password <span class="font-normal text-zinc-400">(at least 10 characters)</span></label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="field h-11">
            @error('password')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field h-11">
        </div>
        <button class="btn-primary w-full">Install the store</button>
    </form>
</x-guest-layout>
