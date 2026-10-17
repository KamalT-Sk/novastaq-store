<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Payments\PaymentGatewayManager;
use App\Support\Fulfilment;
use App\Support\Money;
use App\Support\OrderAccess;
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
        abort_unless($product->is_active && $product->isDigital() && ! $product->isPriceOnDemand(), 404);

        $enabledGateways = array_keys($gateways->enabled());
        $activeVariants = $product->activeVariants;
        $variant = null;

        $rules = [
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', Rule::in($enabledGateways)],
        ];

        if ($activeVariants->isNotEmpty()) {
            $rules['variant_id'] = ['required', 'integer', Rule::in($activeVariants->pluck('id'))];
        }

        $data = $request->validate($rules, [
            'variant_id.required' => 'Please select an option or license.',
        ]);

        if (! empty($data['variant_id'])) {
            $variant = $activeVariants->firstWhere('id', (int) $data['variant_id']);
        }

        $unitPrice = $variant ? $variant->price : $product->price;

        $provider = $data['payment_method'] ?? $gateways->defaultName();
        $gateway = $gateways->get($provider);

        $discountCode = null;
        $discountAmount = 0;
        if ($codeStr = strtoupper(trim((string) $request->input('discount_code', '')))) {
            $dc = DiscountCode::where('code', $codeStr)->first();
            if ($dc && $dc->isValidForProduct($product)) {
                $discountCode = $dc;
                $discountAmount = $dc->calculateDiscount($unitPrice);
            }
        }
        $total = max(0, $unitPrice - $discountAmount);

        $order = DB::transaction(function () use ($data, $product, $variant, $unitPrice, $request, $gateway, $discountCode, $discountAmount, $total) {
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $unitPrice,
                'discount_code_id' => $discountCode?->id,
                'discount_code' => $discountCode?->code,
                'discount_amount' => $discountAmount,
                'delivery_fee' => 0,
                'total' => $total,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'name' => $product->name,
                'variant_name' => $variant?->name,
                'delivery_type' => 'digital',
                'digital_instructions' => $product->digital_instructions,
                'unit_price' => $unitPrice,
                'quantity' => 1,
                'line_total' => $unitPrice,
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

        OrderAccess::grant($request, $order);

        if ($order->total <= 0) {
            app(Fulfilment::class)->markOrderPaidManually($order, 'free_discount');

            return redirect()->route('order.complete', ['order' => $order->number, 'paid' => 1]);
        }

        if ($order->total < 10000) {
            return back()->withInput()->with('error', 'Online payment providers (Paystack, Nomba, Tsara) require a minimum amount of ₦100. The discounted total is '.Money::naira($order->total).'. Please set the product price higher or use a 100% discount for a free order.');
        }

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

            return back()->withInput()->with('error', $e->getMessage() ?: 'We could not reach the payment provider. Please try again in a moment.');
        }

        return redirect()->away($url);
    }
}
