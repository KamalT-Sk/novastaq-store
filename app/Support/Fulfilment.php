<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Payments\VerifiedPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turns a verified payment into a paid order or an active subscription.
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

    /** @param  array<string, mixed>  $data  Paystack's transaction or subscription payload */
    public function activateSubscription(Subscription $sub, array $data): void
    {
        $sub->update(array_filter([
            'status' => 'active',
            'started_at' => $sub->started_at ?? now(),
            'provider_customer_code' => data_get($data, 'customer.customer_code'),
            'provider_subscription_code' => data_get($data, 'subscription_code'),
            'provider_email_token' => data_get($data, 'email_token'),
            'next_payment_at' => ($d = data_get($data, 'next_payment_date')) ? Carbon::parse($d) : null,
        ], fn ($v) => $v !== null));
    }
}
