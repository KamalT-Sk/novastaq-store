<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Payments\VerifiedPayment;
use Illuminate\Support\Facades\DB;

/**
 * Turns a verified payment into a paid order.
 * Both the customer's redirect and Paystack's webhook call this, in any order,
 * so every step is idempotent: paying twice never double-counts stock.
 */
class Fulfilment
{
    public function markOrderPaid(Order $order, VerifiedPayment $payment, string $provider): bool
    {
        if (! $payment->paid || $payment->amountKobo < $order->total) {
            return false;
        }

        return DB::transaction(function () use ($order, $provider) {
            $order = Order::lockForUpdate()->find($order->id);
            if ($order->isPaid()) {
                return true; // already handled by the other path
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_provider' => $provider,
                'paid_at' => now(),
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
            ]);

            foreach ($order->items as $item) {
                $product = $item->product_id ? Product::lockForUpdate()->find($item->product_id) : null;
                if ($product && $product->stock !== null) {
                    $product->update(['stock' => max(0, $product->stock - $item->quantity)]);
                }
            }

            return true;
        });
    }
}
