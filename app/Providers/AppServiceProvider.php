<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\MidtransPaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MidtransPaymentGateway::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('api-public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('api-authenticated', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?? $request->ip()));
    }
}
