<x-guest-layout>
    <div class="w-12 h-12 rounded-2xl bg-zinc-100 text-ink flex items-center justify-center mb-5 mx-auto">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
        </svg>
    </div>

    <h1 class="text-2xl font-semibold tracking-[-0.02em] text-center mb-1">Forgot password</h1>
    <p class="text-[14px] text-zinc-500 text-center mb-6">
        Enter your account email and we'll send you a 6-digit verification code to reset your password.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="label">Email address</label>
            <x-text-input id="email" type="email" name="email" :value="old('email', session('password_reset_email'))" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button class="btn-primary w-full h-11 text-[14px]">Send reset code</button>
    </form>

    <p class="mt-6 pt-6 border-t border-black/[0.06] text-center text-[14px] text-zinc-600">
        Remember your password? <a href="{{ route('login') }}" class="font-medium text-ink underline underline-offset-4">Sign in</a>
    </p>
</x-guest-layout>
