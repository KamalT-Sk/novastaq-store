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
    public function __construct(private readonly Notifier $notifier) {}

    public function markOrderPaid(Order $order, VerifiedPayment $payment, string $provider): bool
    {
        if (! $payment->paid || $payment->amountKobo < $order->total) {
            return false;
        }

        $justPaid = false;
        DB::transaction(function () use ($order, $provider, &$justPaid) {
            $order = Order::lockForUpdate()->find($order->id);
            if ($order->isPaid()) {
                return; // already handled by the other path
            }
            $justPaid = true;

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
        });

        // Emails go out once, after the payment is safely recorded.
        if ($justPaid) {
            $this->notifier->orderPaid($order->fresh('items'));
        }

        return true;
    }

    /**
     * Admin manually marks an order as paid (direct transfer, cash, manual confirmation).
     */
    public function markOrderPaidManually(Order $order, ?string $provider = null): bool
    {
        $justPaid = false;
        DB::transaction(function () use ($order, $provider, &$justPaid) {
            $order = Order::lockForUpdate()->find($order->id);
            if ($order->isPaid()) {
                return;
            }
            $justPaid = true;

            $order->update([
                'payment_status' => 'paid',
                'payment_provider' => $order->payment_provider ?: ($provider ?: 'manual'),
                'paid_at' => now(),
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
            ]);

            foreach ($order->items as $item) {
                $product = $item->product_id ? Product::lockForUpdate()->find($item->product_id) : null;
                if ($product && $product->stock !== null) {
                    $product->update(['stock' => max(0, $product->stock - $item->quantity)]);
                }
            }
        });

        if ($justPaid) {
            $this->notifier->orderPaid($order->fresh('items'));
        }

        return $justPaid;
    }
}
