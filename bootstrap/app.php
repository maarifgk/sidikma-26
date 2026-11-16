<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The production site is served through a TLS reverse proxy. Trust
        // forwarded host/proto headers so Livewire signed upload URLs are
        // generated and validated against the same HTTPS request URL.
        $middleware->trustProxies('*');
        $middleware->append(SecurityHeaders::class);
        $middleware->validateCsrfTokens(except: ['payments/midtrans/notification']);

        $middleware->redirectGuestsTo(
            fn (): string => route('filament.admin.auth.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
