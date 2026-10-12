<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * "Buy now" for a service plan (e.g. X Premium, 1 month): one product, paid once, no delivery.
 * The customer tells us which account to activate; an admin does it and marks the order done.
 */
class ServiceOrderController extends Controller
{
    public function store(Request $request, Product $product, PaymentGatewayManager $gateways): RedirectResponse
    {
        abort_unless($product->is_active && $product->isService(), 404);

        $data = $request->validate([
            'account_details' => ['required', 'string', 'max:500'],
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'in:paystack,tsara'],
        ], [
            'account_details.required' => 'Tell us which account to activate.',
        ]);

        $provider = $data['payment_method'] ?? 'paystack';
        $gateway = $gateways->get($provider);

        $order = DB::transaction(function () use ($data, $product, $request, $gateway) {
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $product->price,
                'delivery_fee' => 0,
                'total' => $product->price,
            ]);
            $order->items()->create([
                'product_id' => $product->id,
                'name' => $product->name,
                'account_details' => $data['account_details'],
                'unit_price' => $product->price,
                'quantity' => 1,
                'line_total' => $product->price,
            ]);
            $order->update([
                'payment_reference' => $order->number,
                'payment_provider' => $gateway->name(),
            ]);

            return $order;
        });

        try {
            $url = $gateway->initialize(
                $order->email,
                $order->total,
                $order->payment_reference,
                route('checkout.callback', ['provider' => $gateway->name()]),
                [
                    'order_id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'phone' => $order->phone,
                    'cancel_url' => route('product', $product),
                ]
            );
        } catch (RuntimeException $e) {
            report($e);

            return back()->withInput()->with('error', 'We could not reach the payment provider. Please try again in a moment.');
        }

        return redirect()->away($url);
    }
}
