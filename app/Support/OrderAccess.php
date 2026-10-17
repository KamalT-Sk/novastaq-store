<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;

final class OrderAccess
{
    public static function grant(Request $request, Order $order): void
    {
        $request->session()->put('order_access.'.$order->id, true);
    }

    public static function authorize(Request $request, Order $order): void
    {
        $user = $request->user();
        abort_unless(
            $request->hasValidSignature()
            || $request->session()->get('order_access.'.$order->id) === true
            || ($user && ((int) $order->user_id === (int) $user->id || $user->hasAccess('orders'))),
            403, 'Use the link in your confirmation email or sign in to your account.'
        );
    }
}
