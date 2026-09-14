<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('/auth/register', [AuthApiController::class, 'register'])->name('auth.register');
        Route::post('/auth/login', [AuthApiController::class, 'login'])->name('auth.login');
    });

    Route::middleware('throttle:api-public')->group(function () {
        Route::get('/categories', [CategoryApiController::class, 'index'])->name('categories.index');
        Route::get('/categories/{category:slug}', [CategoryApiController::class, 'show'])->name('categories.show');
        Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
        Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('products.show');
    });

    Route::middleware([ApiTokenMiddleware::class, 'throttle:api-authenticated'])->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');
        Route::get('/orders', [OrderApiController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderApiController::class, 'show'])->name('orders.show');
    });
});
