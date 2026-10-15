<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset request by generating a 6-digit OTP.
     */
    public function store(Request $request, Notifier $notifier): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        session(['password_reset_email' => $email]);

        if ($user) {
            $code = EmailOtp::generate($user->email, 'password_reset');
            $notifier->passwordResetOtp($user, $code);
        }

        return redirect()->route('password.reset')->with('status', 'We sent a 6-digit password reset code to '.$email.'. Enter it below.');
    }

    /**
     * Resend a fresh 6-digit password reset OTP.
     */
    public function resend(Request $request, Notifier $notifier): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if ($user) {
            $code = EmailOtp::generate($user->email, 'password_reset');
            $notifier->passwordResetOtp($user, $code);
        }

        return back()->with('status', 'A new 6-digit reset code has been sent to '.$email.'.');
    }
}
