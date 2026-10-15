<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductDigitalKey;
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

            $this->processPaidItems($order);
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

            $this->processPaidItems($order);
        });

        if ($justPaid) {
            $this->notifier->orderPaid($order->fresh('items'));
        }

        return $justPaid;
    }

    private function processPaidItems(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::lockForUpdate()->find($item->product_id) : null;
            if ($product && $product->stock !== null) {
                $product->update(['stock' => max(0, $product->stock - $item->quantity)]);
            }

            if (($item->isDigital() || ($product && $product->isDigital())) && empty($item->digital_content) && empty($item->digital_file)) {
                $this->fulfillDigitalItem($item, $product);
            }
        }

        // Purely digital orders with all items fulfilled transition automatically to completed!
        if ($order->isDigital() && $order->pendingDigitalItems()->isEmpty()) {
            $order->update(['status' => 'completed']);
        }
    }

    private function fulfillDigitalItem(OrderItem $item, ?Product $product): void
    {
        if (! $product) {
            return;
        }

        $item->delivery_type = 'digital';
        $item->digital_instructions = $product->digital_instructions;

        if ($product->digital_type === 'pool') {
            $key = ProductDigitalKey::where('product_id', $product->id)
                ->whereNull('order_item_id')
                ->lockForUpdate()
                ->first();

            if ($key) {
                $key->update([
                    'order_item_id' => $item->id,
                    'assigned_at' => now(),
                ]);
                $item->digital_content = $key->key_content;
            }
        } else {
            $item->digital_content = $product->digital_url;
            $item->digital_file = $product->digital_file;
        }

        $item->save();
    }
}
