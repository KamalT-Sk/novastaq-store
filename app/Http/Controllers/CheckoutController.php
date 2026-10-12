<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\PaymentGatewayManager;
use App\Support\Cart;
use App\Support\Fulfilment;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    public function show(Cart $cart): View|RedirectResponse
    {
        $lines = $cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('shop.checkout', ['lines' => $lines, 'subtotal' => $cart->subtotal(), 'deliveryFee' => $this->deliveryFee()]);
    }

    public function store(Request $request, Cart $cart, PaymentGatewayManager $gateways): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'in:paystack,tsara'],
        ]);

        $provider = $data['payment_method'] ?? 'paystack';
        $gateway = $gateways->get($provider);

        $lines = $cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }
        foreach ($lines as $line) {
            if (! $line->product->inStock($line->quantity)) {
                return redirect()->route('cart')->with('error', "{$line->product->name} no longer has enough stock. Please update your cart.");
            }
        }

        $subtotal = $lines->sum('total');
        $order = DB::transaction(function () use ($data, $lines, $subtotal, $request) {
            $order = Order::create([
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()?->id,
                'subtotal' => $subtotal,
                'delivery_fee' => $this->deliveryFee(),
                'total' => $subtotal + $this->deliveryFee(),
            ]);
            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'name' => $line->product->name,
                    'unit_price' => $line->product->price,
                    'quantity' => $line->quantity,
                    'line_total' => $line->total,
                ]);
            }

            return $order;
        });

        $order->update(['payment_reference' => $order->number, 'payment_provider' => $gateway->name()]);

        try {
            $url = $gateway->initialize(
                $order->email,
                $order->total,
                $order->number,
                route('checkout.callback', ['provider' => $gateway->name()]),
                [
                    'order_id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'phone' => $order->phone,
                    'cancel_url' => route('checkout'),
                ]
            );
        } catch (RuntimeException $e) {
            report($e);

            return back()->withInput()->with('error', 'We could not reach the payment provider. Please try again in a moment.');
        }

        return redirect()->away($url);
    }

    /** Payment provider sends the customer back here with ?reference=... or ?trx_id=... */
    public function callback(Request $request, PaymentGatewayManager $gateways, Fulfilment $fulfilment, Cart $cart): View
    {
        $trxId = (string) $request->query('trx_id');
        $reference = (string) $request->query('reference');

        $order = null;
        if ($trxId !== '') {
            $order = Order::where('payment_reference', $trxId)->orWhere('number', $trxId)->first();
        }
        if (! $order && $reference !== '') {
            $order = Order::where('payment_reference', $reference)->orWhere('number', $reference)->first();
        }

        abort_unless($order, 404);

        $provider = (string) ($request->query('provider') ?: ($order->payment_provider ?: 'paystack'));
        $gateway = $gateways->get($provider);

        $paid = $order->isPaid();
        if (! $paid) {
            try {
                $paid = $fulfilment->markOrderPaid($order, $gateway->verify($order->payment_reference), $gateway->name());
            } catch (RuntimeException $e) {
                report($e); // the webhook will still confirm it
            }
        }

        // A service bought with "Buy now" never touched the cart, so leave any gadgets in it.
        if ($paid && ! $order->isService()) {
            $cart->clear();
        }

        return view('shop.order-complete', ['order' => $order->fresh('items'), 'paid' => $paid]);
    }

    private function deliveryFee(): int
    {
        return Money::toKobo((string) config('store.delivery_fee', 0));
    }
}
