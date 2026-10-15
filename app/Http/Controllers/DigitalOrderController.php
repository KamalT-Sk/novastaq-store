<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * "Instant Buy" for a digital product (e.g. Gemini AI invite link, eBook/PDF, license key).
 * One product, instant checkout with no delivery address or fee.
 */
class DigitalOrderController extends Controller
{
    public function store(Request $request, Product $product, PaymentGatewayManager $gateways): RedirectResponse
    {
        abort_unless($product->is_active && $product->isDigital(), 404);

        $enabledGateways = array_keys($gateways->enabled());
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', Rule::in($enabledGateways)],
        ]);

        $provider = $data['payment_method'] ?? $gateways->defaultName();
        $gateway = $gateways->get($provider);

        $discountCode = null;
        $discountAmount = 0;
        if ($codeStr = strtoupper(trim((string) $request->input('discount_code', '')))) {
            $dc = \App\Models\DiscountCode::where('code', $codeStr)->first();
            if ($dc && $dc->isValidForProduct($product)) {
                $discountCode = $dc;
                $discountAmount = $dc->calculateDiscount($product->price);
            }
        }
        $total = max(0, $product->price - $discountAmount);

        $order = DB::transaction(function () use ($data, $product, $request, $gateway, $discountCode, $discountAmount, $total) {
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $product->price,
                'discount_code_id' => $discountCode?->id,
                'discount_code' => $discountCode?->code,
                'discount_amount' => $discountAmount,
                'delivery_fee' => 0,
                'total' => $total,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'name' => $product->name,
                'delivery_type' => 'digital',
                'digital_instructions' => $product->digital_instructions,
                'unit_price' => $product->price,
                'quantity' => 1,
                'line_total' => $product->price,
            ]);

            $order->update([
                'payment_reference' => $order->number,
                'payment_provider' => $gateway->name(),
            ]);

            if ($discountCode) {
                $discountCode->recordUse();
            }

            if ($request->user() && empty($request->user()->phone)) {
                $request->user()->updateQuietly(['phone' => $data['phone']]);
            }

            session([
                'customer_phone' => $data['phone'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['email'],
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

