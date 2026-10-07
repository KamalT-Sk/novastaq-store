<?php

namespace App\Providers;

use App\Mail\MailhiveTransport;
use App\Payments\PaymentGateway;
use App\Payments\PaystackGateway;
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
