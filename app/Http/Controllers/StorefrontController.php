<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View { $products=Product::where('is_active',true)->with('category')->latest()->take(8)->get();$categories=Category::where('is_active',true)->withCount('products')->orderBy('name')->get();return view('store.home',compact('products','categories')); }
    public function products(Request $request): View { $products=Product::where('is_active',true)->with('category')->when($request->filled('search'),fn($q)=>$q->where('name','like','%'.$request->search.'%'))->latest()->paginate(12)->withQueryString();return view('store.products',compact('products')); }
    public function show(Product $product): View { abort_unless($product->is_active,404);$product->load('category','variants.attributeValues.attribute');return view('store.product',compact('product')); }
    public function category(Category $category): View { abort_unless($category->is_active,404);$products=$category->products()->where('is_active',true)->latest()->paginate(12);return view('store.products',compact('products','category')); }
}
