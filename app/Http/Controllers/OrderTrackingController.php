<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    /**
     * Show the track order search page or auto-display results if query params provided.
     */
    public function show(Request $request): View
    {
        $number = trim((string) $request->query('number', ''));
        $contact = trim((string) $request->query('contact', ''));

        $order = null;
        if ($number !== '' && $contact !== '') {
            $order = $this->findOrder($number, $contact);
        }

        return view('orders.track', [
            'order' => $order,
            'number' => $number,
            'contact' => $contact,
        ]);
    }

    /**
     * Handle the tracking form submission.
     */
    public function track(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:50'],
            'contact' => ['required', 'string', 'max:190'],
        ]);

        $order = $this->findOrder($data['number'], $data['contact']);

        if (! $order) {
            return back()->withInput()->with('error', "We couldn't find an order matching #{$data['number']} and {$data['contact']}. Please check your order confirmation details and try again.");
        }

        return view('orders.track', [
            'order' => $order,
            'number' => $data['number'],
            'contact' => $data['contact'],
        ]);
    }

    /**
     * Query order safely matching order number and email or phone.
     */
    protected function findOrder(string $rawNumber, string $rawContact): ?Order
    {
        $number = ltrim(trim($rawNumber), '#');
        $contact = trim(strtolower($rawContact));
        $phoneDigits = preg_replace('/[^\d+]/', '', $contact);

        return Order::with(['items.product.images', 'items.variant'])
            ->where(function ($q) use ($number) {
                $q->where('number', $number)
                    ->orWhere('number', 'like', '%'.$number);
            })
            ->where(function ($q) use ($contact, $phoneDigits) {
                $q->whereRaw('LOWER(email) = ?', [$contact]);
                if ($phoneDigits !== '') {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ['%'.ltrim($phoneDigits, '+').'%']);
                }
            })
            ->latest('id')
            ->first();
    }
}
