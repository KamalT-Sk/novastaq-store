<x-guest-layout>
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-1">Welcome back</h1>
    <p class="text-[14px] text-zinc-500 mb-6">Sign in to see your orders and plans.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <div class="flex items-baseline justify-between">
                <label for="password" class="label">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[13px] text-zinc-500 hover:text-ink underline underline-offset-2">Forgot password?</a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label for="remember_me" class="flex items-center gap-2 text-[14px] text-zinc-600">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-black/20 text-ink focus:ring-ink"> Keep me signed in
        </label>
        <button class="btn-primary w-full">Sign in</button>
    </form>

    <p class="mt-6 pt-6 border-t border-black/[0.06] text-center text-[14px] text-zinc-600">
        New here? <a href="{{ route('register') }}" class="font-medium text-ink underline underline-offset-4">Create an account</a>
    </p>
</x-guest-layout>
