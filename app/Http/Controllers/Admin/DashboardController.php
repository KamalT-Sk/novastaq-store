<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $paid = Order::where('payment_status', 'paid');
        $open = (clone $paid)->whereIn('status', ['processing', 'shipped']);

        return view('admin.dashboard', [
            'revenueMonth' => (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            'ordersToday' => (clone $paid)->where('paid_at', '>=', now()->startOfDay())->count(),
            'servicesToDo' => (clone $open)->whereNull('address')->count(),
            'devicesToShip' => (clone $open)->whereNotNull('address')->count(),
            'serviceQueue' => (clone $open)->whereNull('address')->with('items')->oldest('paid_at')->take(10)->get(),
            'lowStock' => Product::active()->whereNotNull('stock')->where('stock', '<=', 3)->orderBy('stock')->take(8)->get(),
            'recentOrders' => Order::with('items')->latest()->take(8)->get(),
        ]);
    }
}
