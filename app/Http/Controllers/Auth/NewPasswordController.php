<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, ?string $token = null): View
    {
        $email = $request->email ?? session('password_reset_email') ?? '';

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Handle an incoming new password request (via 6-digit OTP or token).
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = strtolower(trim($request->email));

        // 1. If 6-digit OTP code was provided
        if ($request->filled('code')) {
            $request->validate([
                'code' => ['required', 'string', 'size:6'],
            ], [
                'code.size' => 'The reset code must be exactly 6 digits.',
            ]);

            $verified = EmailOtp::verify($email, $request->code, 'password_reset');

            if (! $verified) {
                throw ValidationException::withMessages([
                    'code' => 'The reset code is invalid or has expired. Please check your email or request a new code.',
                ]);
            }

            $user = User::where('email', $email)->first();
            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => 'We could not find an account with that email address.',
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
            session()->forget('password_reset_email');

            return redirect()->route('login')->with('status', 'Your password has been reset successfully! You can now sign in.');
        }

        // 2. If token was provided (e.g. staff invite or link)
        if ($request->filled('token')) {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            return $status == Password::PASSWORD_RESET
                ? redirect()->route('login')->with('status', __($status))
                : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        throw ValidationException::withMessages([
            'code' => 'Please enter the 6-digit reset code sent to your email.',
        ]);
    }
}
