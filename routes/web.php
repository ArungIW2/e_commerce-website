<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
Route::get('/products/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/categories/{category:slug}', [StorefrontController::class, 'category'])->name('categories.show');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart');
