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
Route::get('/orders/{order:number}', [CheckoutController::class, 'orderComplete'])->name('order.complete');
Route::get('/orders/{order:number}/pay', [CheckoutController::class, 'pay'])->name('orders.pay');

// Services (e.g. X Premium): "Buy now", pay once, we activate it
Route::post('/buy/{product}', [ServiceOrderController::class, 'store'])->middleware('throttle:10,1')->name('service.buy');

// Digital products (e.g. Gemini AI links, PDF guides): "Instant buy", pay once, instant digital delivery
Route::post('/buy-digital/{product}', [DigitalOrderController::class, 'store'])->middleware('throttle:10,1')->name('digital.buy');

// Download paid digital file attachments
Route::get('/orders/{order:number}/download/{item}', [DownloadController::class, 'download'])->name('orders.download');

// Instant discount check
Route::post('/discount/check', [ShopController::class, 'checkDiscount'])->middleware('throttle:30,1')->name('discount.check');

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
        Route::match(['GET', 'POST', 'DELETE'], 'products/{product}/keys/{key}', [Admin\ProductController::class, 'destroyKey'])->withoutScopedBindings()->name('products.keys.destroy');
        Route::match(['GET', 'POST', 'DELETE'], 'products/{product}/keys/{key}/delete', [Admin\ProductController::class, 'destroyKey'])->withoutScopedBindings()->name('products.keys.destroy.alias');
        Route::match(['GET', 'POST', 'DELETE'], 'digital-keys/{key}', [Admin\ProductController::class, 'destroyKeyDirect'])->name('digital-keys.destroy');
        Route::match(['GET', 'POST', 'DELETE'], 'digital-keys/{key}/delete', [Admin\ProductController::class, 'destroyKeyDirect'])->name('digital-keys.destroy.alias');
        Route::post('products/{product}/images', [Admin\ProductController::class, 'uploadImages'])->name('products.images.store');
        Route::delete('products/{product}/images/{image}', [Admin\ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::delete('products/{product}/images', [Admin\ProductController::class, 'destroyImagesBatch'])->name('products.images.destroy-batch');
        Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->middleware('admin:delete')->name('categories.destroy');
        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update']);
        Route::post('products/{product}/discounts', [Admin\DiscountCodeController::class, 'storeForProduct'])->name('products.discounts.store');
        Route::patch('discounts/{discount}/toggle', [Admin\DiscountCodeController::class, 'toggle'])->name('discounts.toggle');
        Route::resource('discounts', Admin\DiscountCodeController::class)->only(['index', 'store', 'destroy']);
        Route::post('system/catalogue', [Admin\SystemController::class, 'catalogue'])->name('system.catalogue');
    });

    Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update'])->middleware('admin:orders');
    Route::post('orders/{order}/mark-paid', [Admin\OrderController::class, 'markPaid'])->middleware('admin:orders')->name('orders.mark-paid');
    Route::post('orders/{order}/items/{item}/deliver-digital', [Admin\OrderController::class, 'deliverDigital'])->middleware('admin:orders')->name('orders.deliver-digital');
    Route::post('orders/{order}/reminder', [Admin\OrderController::class, 'sendReminder'])->middleware('admin:orders')->name('orders.reminder');
    Route::post('orders/reminders/batch', [Admin\OrderController::class, 'sendBatchReminders'])->middleware('admin:orders')->name('orders.reminders.batch');

    Route::middleware('admin:orders')->group(function () {
        Route::get('customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/broadcast', [Admin\EmailController::class, 'broadcast'])->name('customers.broadcast');
        Route::post('customers/broadcast/test', [Admin\EmailController::class, 'sendTest'])->name('customers.broadcast.test');
        Route::post('customers/broadcast', [Admin\EmailController::class, 'sendBroadcast'])->name('customers.broadcast.send');

        // Emails Hub
        Route::get('emails', [Admin\EmailController::class, 'broadcast'])->name('emails.index');
        Route::get('emails/broadcast', [Admin\EmailController::class, 'broadcast'])->name('emails.broadcast');
        Route::post('emails/broadcast/test', [Admin\EmailController::class, 'sendTest'])->name('emails.broadcast.test');
        Route::post('emails/broadcast', [Admin\EmailController::class, 'sendBroadcast'])->name('emails.broadcast.send');
        Route::get('emails/broadcasts/{broadcast}/preview', [Admin\EmailController::class, 'preview'])->name('emails.broadcast.preview');
        Route::delete('emails/broadcasts/{broadcast}', [Admin\EmailController::class, 'destroyBroadcast'])->name('emails.broadcast.destroy');
        Route::get('emails/automated', [Admin\EmailController::class, 'automated'])->name('emails.automated');
        Route::get('emails/settings', [Admin\EmailController::class, 'settings'])->name('emails.settings');
        Route::post('emails/settings', [Admin\EmailController::class, 'updateSettings'])->name('emails.settings.update');
        Route::post('emails/settings/test', [Admin\EmailController::class, 'testDiagnostics'])->name('emails.settings.test');
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
        Route::match(['GET', 'POST'], 'system/update', [Admin\SystemController::class, 'migrate'])->name('system.update');
    });
});

require __DIR__.'/auth.php';
