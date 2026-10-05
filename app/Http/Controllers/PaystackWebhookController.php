<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Subscription;
use App\Payments\PaymentGateway;
use App\Support\Fulfilment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Paystack calls this server to server. Set it in Paystack:
 * Settings, API Keys & Webhooks, Webhook URL: https://store.novastaq.com/paystack/webhook
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, Fulfilment $fulfilment): Response
    {
        abort_unless($gateway->isValidWebhook($request), 401);

        $data = (array) $request->input('data', []);

        match ((string) $request->input('event')) {
            'charge.success' => $this->chargeSuccess((string) data_get($data, 'reference'), $data, $gateway, $fulfilment),
            'subscription.create' => $this->subscriptionCreated($data, $fulfilment),
            'subscription.not_renew' => $this->setStatus($data, 'non-renewing'),
            'subscription.disable' => $this->setStatus($data, 'cancelled', ['cancelled_at' => now()]),
            'invoice.payment_failed' => $this->setStatus((array) data_get($data, 'subscription', []), 'attention'),
            'invoice.update' => $this->renewed($data),
            default => null,
        };

        return response('ok');
    }

    private function chargeSuccess(string $reference, array $data, PaymentGateway $gateway, Fulfilment $fulfilment): void
    {
        if ($order = Order::where('payment_reference', $reference)->first()) {
            // re-verify with Paystack rather than trusting the amount in the payload
            $fulfilment->markOrderPaid($order, $gateway->verify($reference), $gateway->name());
        } elseif (($sub = Subscription::where('payment_reference', $reference)->first()) && $sub->status === 'pending') {
            $fulfilment->activateSubscription($sub, $data);
        }
    }

    private function subscriptionCreated(array $data, Fulfilment $fulfilment): void
    {
        $sub = Subscription::whereNull('provider_subscription_code')
            ->where('email', data_get($data, 'customer.email'))
            ->whereHas('product', fn ($q) => $q->where('paystack_plan_code', data_get($data, 'plan.plan_code')))
            ->latest()->first();

        if ($sub) {
            $fulfilment->activateSubscription($sub, $data);
        }
    }

    private function setStatus(array $data, string $status, array $extra = []): void
    {
        if ($code = data_get($data, 'subscription_code')) {
            Subscription::where('provider_subscription_code', $code)->update(['status' => $status] + $extra);
        }
    }

    private function renewed(array $data): void
    {
        if (data_get($data, 'paid') && ($code = data_get($data, 'subscription.subscription_code'))) {
            Subscription::where('provider_subscription_code', $code)->update(array_filter([
                'status' => 'active',
                'next_payment_at' => ($d = data_get($data, 'subscription.next_payment_date')) ? Carbon::parse($d) : null,
            ]));
        }
    }
}
