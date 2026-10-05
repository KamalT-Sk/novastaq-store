<?php

namespace App\Providers;

use App\Payments\PaymentGateway;
use App\Payments\PaystackGateway;
use App\Support\Cart;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swap this binding to change payment provider.
        $this->app->singleton(PaymentGateway::class, fn () => new PaystackGateway(config('services.paystack.secret_key')));
        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        // @naira($kobo) prints a price, e.g. 125000000 -> ₦1,250,000
        Blade::directive('naira', fn ($kobo) => "<?php echo e(\App\Support\Money::naira($kobo)); ?>");

        View::composer('layouts.app', fn ($view) => $view->with([
            'cartCount' => app(Cart::class)->count(),
            'navCategories' => \App\Models\Category::orderBy('sort')->get(),
        ]));
    }
}
