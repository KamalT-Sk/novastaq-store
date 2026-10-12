<?php

namespace App\Providers;

use App\Mail\MailhiveTransport;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaystackGateway;
use App\Payments\TsaraGateway;
use App\Support\Cart;
use App\Support\Notifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaystackGateway::class, fn () => new PaystackGateway(config('services.paystack.secret_key')));
        $this->app->singleton(TsaraGateway::class, fn () => new TsaraGateway(
            config('services.tsara.public_key'),
            config('services.tsara.secret_key'),
            config('services.tsara.base_url', 'https://api.tsara.ng/v1'),
        ));
        $this->app->singleton(\App\Payments\NombaGateway::class, fn () => new \App\Payments\NombaGateway(
            config('services.nomba.client_id'),
            config('services.nomba.client_secret'),
            config('services.nomba.account_id'),
            config('services.nomba.base_url', 'https://api.nomba.com'),
            config('services.nomba.webhook_secret'),
        ));

        $this->app->singleton(PaymentGatewayManager::class, fn ($app) => new PaymentGatewayManager([
            $app->make(PaystackGateway::class),
            $app->make(TsaraGateway::class),
            $app->make(\App\Payments\NombaGateway::class),
        ]));

        $this->app->bind(PaymentGateway::class, fn ($app) => $app->make(PaymentGatewayManager::class)->get());
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

        View::composer(['shop.checkout', 'shop.show'], fn ($view) => $view->with([
            'availableGateways' => app(PaymentGatewayManager::class)->enabled(),
        ]));

        // Laravel's own emails (password reset etc.) go through Mailhive when MAIL_MAILER=mailhive
        Mail::extend('mailhive', fn () => new MailhiveTransport((string) config('services.mailhive.key'), (string) config('services.mailhive.url')));

        // Password reset email in the store's own design
        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Reset your Novastaq Store password')
            ->view('emails.reset-password', [
                'url' => route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]),
                'minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]));

        // A short welcome email when someone creates an account
        Event::listen(Registered::class, fn (Registered $e) => app(Notifier::class)->welcome($e->user));
    }
}
