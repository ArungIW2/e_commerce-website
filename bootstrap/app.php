<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
RateLimiter::for('api-public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
RateLimiter::for('api-authenticated', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?? $request->ip()));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'payments/midtrans/notification',
            'webhooks/biteship',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
