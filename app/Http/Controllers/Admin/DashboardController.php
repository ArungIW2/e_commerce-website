<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'products' => Product::count(),
            'categories' => Category::count(),
            'customers' => User::where('role', 'customer')->count(),
            'lowStock' => Product::where('stock', '>', 0)->where('stock', '<=', 5)->count(),
            'outOfStock' => Product::where('stock', 0)->count(),
        ]);
    }
}
