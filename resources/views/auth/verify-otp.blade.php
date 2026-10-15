<x-guest-layout>
    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-5 mx-auto">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
        </svg>
    </div>

    <h1 class="text-2xl font-semibold tracking-[-0.02em] text-center mb-1">Check your email</h1>
    <p class="text-[14px] text-zinc-500 text-center mb-6">
        We sent a 6-digit verification code to<br>
        <span class="font-semibold text-ink">{{ $email }}</span>
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('register.verify.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="code" class="sr-only">6-digit verification code</label>
            <input id="code"
                   type="text"
                   name="code"
                   maxlength="6"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   autocomplete="one-time-code"
                   placeholder="· · · · · ·"
                   required
                   autofocus
                   class="field text-center font-mono text-2xl tracking-[0.3em] font-semibold h-14 @error('code') !border-red-500 @enderror">
            <x-input-error :messages="$errors->get('code')" class="mt-2 text-center" />
        </div>

        <button class="btn-primary w-full h-11 text-[14px]">Verify & create account</button>
    </form>

    <div class="mt-6 pt-5 border-t border-black/[0.06] flex items-center justify-between text-[13px] text-zinc-500">
        <form method="POST" action="{{ route('register.resend') }}">
            @csrf
            <span>Didn't get it?</span>
            <button type="submit" class="font-medium text-ink underline underline-offset-4 ml-1">Resend code</button>
        </form>

        <a href="{{ route('register') }}" class="text-zinc-500 hover:text-ink underline underline-offset-4">Change email</a>
    </div>
</x-guest-layout>
