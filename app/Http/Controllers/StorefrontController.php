<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function index()
    {
        return view('store.home', [
            'products' => Product::with('category')->where('is_active', true)->latest()->take(8)->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')->orWhere('sku', 'like', '%'.$request->q.'%'));
        return view('store.products', ['products' => $query->latest()->paginate(12)->withQueryString()]);
    }

    public function show(Product $product) { abort_unless($product->is_active, 404); return view('store.product', compact('product')); }
    public function category(Category $category) { return view('store.products', ['products' => $category->products()->where('is_active', true)->latest()->paginate(12)]); }
}
