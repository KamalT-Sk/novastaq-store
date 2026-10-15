<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Password;

/** The store's emails: one method per moment worth telling someone about. */
class Notifier
{
    public function __construct(private readonly Mailhive $mail) {}

    /** Payment confirmed: tell the shop owner, and send the customer a receipt. */
    public function orderPaid(Order $order): void
    {
        $order->loadMissing('items');
        $what = $order->isService() ? 'Plan to activate' : 'New order';

        $this->mail->send(
            (string) config('store.support_email'),
            "🛒 {$what}: {$order->number} · ".Money::naira($order->total),
            view('emails.admin-new-order', ['order' => $order])->render(),
            $order->email,
            ['type' => 'admin-new-order'],
        );

        $this->mail->send(
            $order->email,
            "Order confirmed: {$order->number}",
            view('emails.order-confirmed', ['order' => $order])->render(),
            (string) config('store.support_email'),
            ['type' => 'order-confirmed'],
        );
    }

    /** Admin pressed "Mark as done" on a plan. */
    public function planActivated(Order $order): void
    {
        $order->loadMissing('items');
        $this->mail->send(
            $order->email,
            'Your '.$order->items->first()?->name.' is active',
            view('emails.plan-activated', ['order' => $order])->render(),
            (string) config('store.support_email'),
            ['type' => 'plan-activated'],
        );
    }

    /** A customer just created an account. */
    public function welcome(User $user): void
    {
        $this->mail->send(
            $user->email,
            'Welcome to Novastaq Store',
            view('emails.welcome', ['user' => $user])->render(),
            (string) config('store.support_email'),
            ['type' => 'welcome'],
        );
    }

    /** 6-digit verification code to verify email upon registration. */
    public function registrationOtp(string $email, string $name, string $otp, int $minutes = 15): bool
    {
        return $this->mail->send(
            $email,
            "Your verification code: {$otp}",
            view('emails.registration-otp', [
                'name' => $name,
                'otp' => $otp,
                'minutes' => $minutes,
            ])->render(),
            (string) config('store.support_email'),
            ['type' => 'registration-otp'],
        );
    }

    /** 6-digit OTP code to reset password. */
    public function passwordResetOtp(User $user, string $otp, int $minutes = 15): bool
    {
        return $this->mail->send(
            $user->email,
            "Your password reset code: {$otp}",
            view('emails.password-reset-otp', [
                'user' => $user,
                'otp' => $otp,
                'minutes' => $minutes,
            ])->render(),
            (string) config('store.support_email'),
            ['type' => 'password-reset-otp'],
        );
    }

    /** Owner added someone to the team. New accounts get a link to set their password. */
    public function staffInvite(User $user, User $by, bool $setPassword): bool
    {
        $url = $setPassword
            ? route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email])
            : route('admin.dashboard');

        return $this->mail->send(
            $user->email,
            "{$by->name} added you to Novastaq Store",
            view('emails.staff-invite', [
                'user' => $user,
                'by' => $by,
                'url' => $url,
                'setPassword' => $setPassword,
                'minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ])->render(),
            $by->email,
            ['type' => 'staff-invite'],
        );
    }

    /** Admin marked a device order as shipped. */
    public function orderShipped(Order $order): void
    {
        $order->loadMissing('items');
        $this->mail->send(
            $order->email,
            "Your order {$order->number} is on its way",
            view('emails.order-shipped', ['order' => $order])->render(),
            (string) config('store.support_email'),
            ['type' => 'order-shipped'],
        );
    }

    /** Admin marked an order as delivered. */
    public function orderDelivered(Order $order): void
    {
        $order->loadMissing('items');
        $this->mail->send(
            $order->email,
            "Your order {$order->number} has been delivered",
            view('emails.order-delivered', ['order' => $order])->render(),
            (string) config('store.support_email'),
            ['type' => 'order-delivered'],
        );
    }

    /** Admin marked an order as cancelled. */
    public function orderCancelled(Order $order): void
    {
        $order->loadMissing('items');
        $this->mail->send(
            $order->email,
            "Your order {$order->number} has been cancelled",
            view('emails.order-cancelled', ['order' => $order])->render(),
            (string) config('store.support_email'),
            ['type' => 'order-cancelled'],
        );
    }
}
