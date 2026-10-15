<x-guest-layout>
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-1">Set a new password</h1>
    <p class="text-[14px] text-zinc-500 mb-6">
        @if ($token)
            Choose a strong password for your Novastaq Store account.
        @else
            Enter the 6-digit code sent to your email along with your new password.
        @endif
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        @if ($token)
            <input type="hidden" name="token" value="{{ $token }}">
        @endif

        <div>
            <label for="email" class="label">Email address</label>
            <x-text-input id="email" type="email" name="email" :value="old('email', $email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        @if (! $token)
            <div>
                <label for="code" class="label">6-digit reset code</label>
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
                       class="field text-center font-mono text-2xl tracking-[0.3em] font-semibold h-13 @error('code') !border-red-500 @enderror">
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
        @endif

        <div>
            <label for="password" class="label">New password</label>
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirm new password</label>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button class="btn-primary w-full h-11 text-[14px]">Update password</button>
    </form>

    @if (! $token)
        <div class="mt-6 pt-5 border-t border-black/[0.06] flex items-center justify-between text-[13px] text-zinc-500">
            <form method="POST" action="{{ route('password.resend') }}">
                @csrf
                <input type="hidden" name="email" value="{{ old('email', $email) }}">
                <span>Didn't get the code?</span>
                <button type="submit" class="font-medium text-ink underline underline-offset-4 ml-1">Resend code</button>
            </form>

            <a href="{{ route('password.request') }}" class="text-zinc-500 hover:text-ink underline underline-offset-4">Change email</a>
        </div>
    @endif
</x-guest-layout>
