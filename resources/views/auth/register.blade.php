<x-guest-layout>
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-1">Create your account</h1>
    <p class="text-[14px] text-zinc-500 mb-6">Track your orders and plans in one place. It takes a minute.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="label">Full name</label>
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <label for="email" class="label">Email</label>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <label for="password" class="label">Password</label>
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm password</label>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <button class="btn-primary w-full">Create account</button>
        <p class="text-[12px] text-zinc-400 text-center">
            By creating an account you agree to our <a href="{{ config('store.main_site') }}/terms" target="_blank" class="underline">Terms</a> and <a href="{{ config('store.main_site') }}/privacy" target="_blank" class="underline">Privacy Policy</a>.
        </p>
    </form>

    <p class="mt-6 pt-6 border-t border-black/[0.06] text-center text-[14px] text-zinc-600">
        Already have an account? <a href="{{ route('login') }}" class="font-medium text-ink underline underline-offset-4">Sign in</a>
    </p>
</x-guest-layout>
