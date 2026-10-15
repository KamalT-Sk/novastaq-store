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

        $hasPhysical = $lines->contains(fn ($l) => $l->product->isPhysical());
        $deliveryFee = $hasPhysical ? $this->deliveryFee() : 0;

        return view('shop.checkout', [
            'lines' => $lines,
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => $deliveryFee,
            'hasPhysical' => $hasPhysical,
        ]);
    }

    public function store(Request $request, Cart $cart, PaymentGatewayManager $gateways): RedirectResponse
    {
        $lines = $cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }
        foreach ($lines as $line) {
            if (! $line->product->inStock($line->quantity)) {
                return redirect()->route('cart')->with('error', "{$line->product->name} no longer has enough stock. Please update your cart.");
            }
        }

        $hasPhysical = $lines->contains(fn ($l) => $l->product->isPhysical());
        $deliveryFee = $hasPhysical ? $this->deliveryFee() : 0;

        $enabledGateways = array_keys($gateways->enabled());
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => [$hasPhysical ? 'required' : 'nullable', 'string', 'max:500'],
            'city' => [$hasPhysical ? 'required' : 'nullable', 'string', 'max:80'],
            'state' => [$hasPhysical ? 'required' : 'nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', \Illuminate\Validation\Rule::in($enabledGateways)],
        ]);

        $provider = $data['payment_method'] ?? $gateways->defaultName();
        $gateway = $gateways->get($provider);

        $subtotal = $lines->sum('total');
        $discountCode = null;
        $discountAmount = 0;
        if ($codeStr = strtoupper(trim((string) $request->input('discount_code', '')))) {
            $dc = \App\Models\DiscountCode::where('code', $codeStr)->first();
            if ($dc && $dc->is_active && (! $dc->expires_at || $dc->expires_at->isFuture()) && ($dc->max_uses === null || $dc->uses_count < $dc->max_uses)) {
                if ($dc->product_id === null) {
                    $discountCode = $dc;
                    $discountAmount = $dc->calculateDiscount($subtotal);
                } else {
                    $matchingLine = $lines->first(fn ($l) => (int) $l->product->id === (int) $dc->product_id);
                    if ($matchingLine) {
                        $discountCode = $dc;
                        $discountAmount = $dc->calculateDiscount($matchingLine->total);
                    }
                }
            }
        }
        $total = max(0, $subtotal - $discountAmount + $deliveryFee);

        $order = DB::transaction(function () use ($data, $lines, $subtotal, $deliveryFee, $request, $discountCode, $discountAmount, $total) {
            $order = Order::create([
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()?->id,
                'subtotal' => $subtotal,
                'discount_code_id' => $discountCode?->id,
                'discount_code' => $discountCode?->code,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
            ]);
            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'name' => $line->product->name,
                    'delivery_type' => $line->product->isDigital() ? 'digital' : 'physical',
                    'digital_instructions' => $line->product->digital_instructions,
                    'unit_price' => $line->product->price,
                    'quantity' => $line->quantity,
                    'line_total' => $line->total,
                ]);
            }

            if ($discountCode) {
                $discountCode->recordUse();
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
        $reference = (string) ($request->query('reference') ?? $request->query('orderReference') ?? $request->query('orderId') ?? '');

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

    /** Allows a customer to retry/complete payment for an unpaid order (e.g. from reminder email). */
    public function pay(Request $request, Order $order, PaymentGatewayManager $gateways): RedirectResponse
    {
        if ($order->isPaid()) {
            return redirect()->route('checkout.callback', ['reference' => $order->number])
                ->with('status', 'This order has already been paid for.');
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('shop')->with('error', 'This order was cancelled. Please place a new order.');
        }

        $provider = $order->payment_provider ?: $gateways->defaultName();
        $gateway = $gateways->get($provider);

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
                    'cancel_url' => route('shop'),
                ]
            );
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('shop')->with('error', 'Could not reach payment provider. Please try again or chat with us.');
        }

        return redirect()->away($url);
    }

    private function deliveryFee(): int
    {
        return Money::toKobo((string) config('store.delivery_fee', 0));
    }
}
