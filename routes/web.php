<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\ShopController;
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

// Services (e.g. X Premium): "Buy now", pay once, we activate it
Route::post('/buy/{product}', [ServiceOrderController::class, 'store'])->middleware('throttle:10,1')->name('service.buy');

Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');

// novastaq.com form messages, relayed to the inbox (see ContactController)
Route::post('/api/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.relay');

// One-time setup on hosts without shell access (see InstallController)
Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'store'])->middleware('throttle:5,1')->name('install.store');

// Customer account (Breeze provides login, register and password reset)
Route::middleware('auth')->group(function () {
    Route::get('/account', fn () => view('dashboard', [
        'orders' => auth()->user()->orders()->with('items')->get(),
    ]))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::patch('products/{product}/toggle', [Admin\ProductController::class, 'toggle'])->name('products.toggle');
    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
    Route::post('system/update', [Admin\SystemController::class, 'migrate'])->name('system.update');
    Route::post('system/catalogue', [Admin\SystemController::class, 'catalogue'])->name('system.catalogue');
});

require __DIR__.'/auth.php';
