<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\PaystackGateway;
use App\Support\Fulfilment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Paystack calls this server to server when a payment succeeds, so orders are confirmed even
 * if the customer closes the tab before returning. Set it in Paystack:
 * Settings, API Keys & Webhooks, Webhook URL: https://store.novastaq.com/paystack/webhook
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackGateway $gateway, Fulfilment $fulfilment): Response
    {
        abort_unless($gateway->isValidWebhook($request), 401);

        if ($request->input('event') === 'charge.success'
            && ($order = Order::where('payment_reference', (string) $request->input('data.reference'))->first())) {
            // re-verify with Paystack rather than trusting the amount in the payload
            $fulfilment->markOrderPaid($order, $gateway->verify($order->payment_reference), $gateway->name());
        }

        return response('ok');
    }
}
