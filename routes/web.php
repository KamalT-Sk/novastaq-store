<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DigitalOrderController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\NombaWebhookController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TsaraWebhookController;
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

// Digital products (e.g. Gemini AI links, PDF guides): "Instant buy", pay once, instant digital delivery
Route::post('/buy-digital/{product}', [DigitalOrderController::class, 'store'])->middleware('throttle:10,1')->name('digital.buy');

// Download paid digital file attachments
Route::get('/orders/{order:number}/download/{item}', [DownloadController::class, 'download'])->name('orders.download');

Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');
Route::post('/tsara/webhook', TsaraWebhookController::class)->name('tsara.webhook');
Route::post('/nomba/webhook', NombaWebhookController::class)->name('nomba.webhook');

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

// Anyone navigating to /dashboard gets sent to their account, admin, or login (never 404)
Route::get('/dashboard', fn () => auth()->check()
    ? redirect()->route(auth()->user()->isStaff() ? 'admin.dashboard' : 'dashboard')
    : redirect()->route('login')
);

// Admin: any staff reaches the dashboard; each area is limited by role (User::ROLES)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('admin:catalogue')->group(function () {
        Route::patch('products/{product}/toggle', [Admin\ProductController::class, 'toggle'])->middleware('admin:publish')->name('products.toggle');
        Route::delete('products/{product}', [Admin\ProductController::class, 'destroy'])->middleware('admin:delete')->name('products.destroy');
        Route::delete('products/{product}/keys/{key}', [Admin\ProductController::class, 'destroyKey'])->name('products.keys.destroy');
        Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->middleware('admin:delete')->name('categories.destroy');
        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update']);
        Route::post('system/catalogue', [Admin\SystemController::class, 'catalogue'])->name('system.catalogue');
    });

    Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update'])->middleware('admin:orders');
    Route::post('orders/{order}/mark-paid', [Admin\OrderController::class, 'markPaid'])->middleware('admin:orders')->name('orders.mark-paid');
    Route::post('orders/{order}/items/{item}/deliver-digital', [Admin\OrderController::class, 'deliverDigital'])->middleware('admin:orders')->name('orders.deliver-digital');

    Route::middleware('admin:orders')->group(function () {
        Route::get('customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/broadcast', [Admin\CustomerController::class, 'broadcastForm'])->name('customers.broadcast');
        Route::post('customers/broadcast/test', [Admin\CustomerController::class, 'sendTest'])->name('customers.broadcast.test');
        Route::post('customers/broadcast', [Admin\CustomerController::class, 'sendBroadcast'])->name('customers.broadcast.send');
    });

    Route::middleware('admin:team')->group(function () {
        Route::get('team', [Admin\TeamController::class, 'index'])->name('team.index');
        Route::post('team', [Admin\TeamController::class, 'store'])->name('team.store');
        Route::patch('team/{user}', [Admin\TeamController::class, 'update'])->name('team.update');
        Route::delete('team/{user}', [Admin\TeamController::class, 'destroy'])->name('team.destroy');
        Route::post('team/{user}/invite', [Admin\TeamController::class, 'resend'])->name('team.resend');
    });

    Route::middleware('admin:system')->group(function () {
        Route::get('payments', [Admin\PaymentGatewayController::class, 'index'])->name('payments.index');
        Route::post('payments/{gateway}/toggle', [Admin\PaymentGatewayController::class, 'toggle'])->name('payments.toggle');
        Route::post('system/update', [Admin\SystemController::class, 'migrate'])->name('system.update');
    });
});

require __DIR__.'/auth.php';
