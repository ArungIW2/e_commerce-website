<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
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
            'orders' => Order::count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'lowStock' => Product::where('stock', '>', 0)->where('stock', '<=', 5)->count(),
            'outOfStock' => Product::where('stock', 0)->count(),
        ]);
    }
}
