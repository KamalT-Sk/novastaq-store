<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'pending' => session('pending_registration'),
        ]);
    }

    /**
     * Handle an incoming registration request: validate, store pending details, and send OTP.
     *
     * @throws ValidationException
     */
    public function store(Request $request, Notifier $notifier): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $pending = [
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
        ];

        session(['pending_registration' => $pending]);

        $code = EmailOtp::generate($pending['email'], 'registration');
        $notifier->registrationOtp($pending['email'], $pending['name'], $code);

        return redirect()->route('register.verify')->with('status', 'We sent a 6-digit verification code to '.$pending['email'].'.');
    }

    /**
     * Display the OTP verification screen.
     */
    public function verifyView(): View|RedirectResponse
    {
        $pending = session('pending_registration');
        if (! $pending) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp', [
            'email' => $pending['email'],
            'name' => $pending['name'],
        ]);
    }

    /**
     * Verify the 6-digit OTP and create the user account.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $pending = session('pending_registration');
        if (! $pending) {
            return redirect()->route('register');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Please enter the 6-digit verification code.',
            'code.size' => 'The verification code must be exactly 6 digits.',
        ]);

        $verified = EmailOtp::verify($pending['email'], $request->code, 'registration');

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is incorrect or has expired. Please check your email or request a new code.',
            ]);
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password'],
            'email_verified_at' => now(),
        ]);

        session()->forget('pending_registration');

        event(new Registered($user));

        Auth::login($user);

        $target = $user->isStaff() ? route('admin.dashboard') : route('dashboard', absolute: false);

        return redirect()->intended($target);
    }

    /**
     * Resend a fresh 6-digit verification code.
     */
    public function resendOtp(Request $request, Notifier $notifier): RedirectResponse
    {
        $pending = session('pending_registration');
        if (! $pending) {
            return redirect()->route('register');
        }

        $code = EmailOtp::generate($pending['email'], 'registration');
        $notifier->registrationOtp($pending['email'], $pending['name'], $code);

        return back()->with('status', 'A new 6-digit verification code has been sent to '.$pending['email'].'.');
    }
}
