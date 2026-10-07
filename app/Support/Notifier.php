<?php

namespace App\Support;

use App\Models\Order;

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
    public function welcome(\App\Models\User $user): void
    {
        $this->mail->send(
            $user->email,
            'Welcome to Novastaq Store',
            view('emails.welcome', ['user' => $user])->render(),
            (string) config('store.support_email'),
            ['type' => 'welcome'],
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
}
