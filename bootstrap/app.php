<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->redirectTo(
            guests: '/login',
            users: fn (\Illuminate\Http\Request $request) => $request->user()?->isStaff() ? '/admin' : '/account',
        );
        // Webhooks post here from provider servers; requests are authenticated by their signatures instead.
        $middleware->validateCsrfTokens(except: ['paystack/webhook', 'tsara/webhook', 'nomba/webhook', 'api/contact']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
