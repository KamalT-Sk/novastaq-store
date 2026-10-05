<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $paid = Order::where('payment_status', 'paid');

        return view('admin.dashboard', [
            'revenueMonth' => (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            'ordersToday' => (clone $paid)->where('paid_at', '>=', now()->startOfDay())->count(),
            'toFulfil' => (clone $paid)->whereIn('status', ['processing', 'shipped'])->count(),
            'activeSubs' => Subscription::whereIn('status', ['active', 'non-renewing'])->count(),
            'lowStock' => Product::active()->whereNotNull('stock')->where('stock', '<=', 3)->orderBy('stock')->take(8)->get(),
            'recentOrders' => Order::latest()->take(8)->get(),
            'attentionSubs' => Subscription::with('product')->where('status', 'attention')->latest()->take(8)->get(),
        ]);
    }
}
