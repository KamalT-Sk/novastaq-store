<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Subscription;
use App\Payments\PaymentGateway;
use App\Support\Fulfilment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class SubscribeController extends Controller
{
    public function store(Request $request, Product $product, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($product->is_active && $product->isSubscription(), 404);
        $user = $request->user();

        try {
            // Plans are created on first use, so prices can be edited freely in the admin.
            if (! $product->paystack_plan_code) {
                $product->update(['paystack_plan_code' => $gateway->createPlan($product->name, $product->price, $product->billing_interval)]);
            }

            $sub = Subscription::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'email' => $user->email,
                'provider' => $gateway->name(),
                'payment_reference' => 'NVS-'.Str::upper(Str::random(12)),
            ]);

            $url = $gateway->initialize($user->email, $product->price, $sub->payment_reference, route('subscribe.callback'),
                ['subscription_id' => $sub->id], $product->paystack_plan_code);
        } catch (RuntimeException $e) {
            report($e);

            return back()->with('error', 'We could not reach the payment provider. Please try again in a moment.');
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, PaymentGateway $gateway, Fulfilment $fulfilment): View
    {
        $sub = Subscription::with('product')->where('payment_reference', (string) $request->query('reference'))->firstOrFail();

        if ($sub->status === 'pending') {
            try {
                $payment = $gateway->verify($sub->payment_reference);
                if ($payment->paid) {
                    $fulfilment->activateSubscription($sub, $payment->raw);
                }
            } catch (RuntimeException $e) {
                report($e);
            }
        }

        return view('shop.subscription-complete', ['subscription' => $sub->fresh('product')]);
    }
}
