<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\MidtransPaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MidtransPaymentGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
