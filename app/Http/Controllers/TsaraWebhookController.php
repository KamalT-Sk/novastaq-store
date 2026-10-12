<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\TsaraGateway;
use App\Support\Fulfilment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Tsara calls this server to server when a payment succeeds.
 * Configure in Tsara Dashboard -> Developers -> Webhooks:
 * Webhook URL: https://store.novastaq.com/tsara/webhook
 */
class TsaraWebhookController extends Controller
{
    public function __invoke(Request $request, TsaraGateway $gateway, Fulfilment $fulfilment): Response
    {
        abort_unless($gateway->isValidWebhook($request), 401);

        $eventType = (string) ($request->input('type') ?? $request->input('event'));
        $data = $request->input('data') ?? [];

        // Common payment success event types in Tsara
        $isSuccessEvent = in_array($eventType, ['payment.success', 'fiat.received', 'offramp.success', 'checkout.success'], true);

        if ($isSuccessEvent) {
            $trxId = (string) ($data['trx_id'] ?? $data['reference'] ?? $request->input('trx_id'));

            if ($trxId !== '') {
                $order = Order::where('payment_reference', $trxId)
                    ->orWhere('number', $trxId)
                    ->first();

                if ($order) {
                    // Re-verify with Tsara rather than trusting the amount in the payload
                    $fulfilment->markOrderPaid($order, $gateway->verify($order->payment_reference), $gateway->name());
                }
            }
        }

        return response('ok');
    }
}
