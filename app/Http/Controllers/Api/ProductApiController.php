<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends ApiController
{
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($request->filled('category')) $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        $products = $query->orderBy('name')->paginate(min(max((int) $request->input('per_page', 15), 1), 50));
        return $this->success($products);
    }

    public function show(Product $product)
    {
        if (!$product->is_active) return $this->error('Product not found.', 404);
        return $this->success($product->load(['category','variants','images']));
    }
}
