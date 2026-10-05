<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SubscribeController;
use Illuminate\Support\Facades\Route;

// Storefront
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/c/{category}', [ShopController::class, 'index'])->name('category');
Route::get('/p/{product}', [ShopController::class, 'show'])->name('product');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart', [CartController::class, 'update'])->name('cart.update');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');

Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');

// Customer account (Breeze provides login, register and password reset)
Route::middleware('auth')->group(function () {
    Route::get('/account', fn () => view('dashboard', [
        'orders' => auth()->user()->orders()->withCount('items')->get(),
        'subscriptions' => auth()->user()->subscriptions()->with('product')->get(),
    ]))->name('dashboard');

    Route::post('/subscribe/{product}', [SubscribeController::class, 'store'])->middleware('throttle:10,1')->name('subscribe');
    Route::get('/subscribe/callback', [SubscribeController::class, 'callback'])->name('subscribe.callback');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
    Route::get('subscriptions', [Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
});

require __DIR__.'/auth.php';
