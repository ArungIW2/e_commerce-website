<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View { $products=Product::where('is_active',true)->with(['category','images'])->latest()->take(8)->get();$categories=Category::where('is_active',true)->withCount('products')->orderBy('name')->get();return view('store.home',compact('products','categories')); }
    public function products(Request $request): View { $term=$request->input('q',$request->input('search'));$products=Product::where('is_active',true)->with(['category','images'])->when($term,fn($query)=>$query->where(fn($q)=>$q->where('name','like','%'.$term.'%')->orWhere('sku','like','%'.$term.'%')))->latest()->paginate(12)->withQueryString();return view('store.products',compact('products')); }
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load('category','images','variants.attributeValues.attribute');
        $reviews = $product->approvedReviews()->with('user')->latest()->paginate(10)->withQueryString();
        $averageRating = round((float) ($product->approvedReviews()->avg('rating') ?? 0), 1);
        $reviewCount = $product->approvedReviews()->count();
        $userReview = auth()->check() ? ProductReview::where('product_id', $product->id)->where('user_id', auth()->id())->first() : null;
        $canReview = auth()->check() && OrderItem::query()->where('product_id', $product->id)->whereHas('order', fn ($query) => $query->where('user_id', auth()->id())->where('status', 'completed'))->exists();

        return view('store.product', compact('product', 'reviews', 'averageRating', 'reviewCount', 'userReview', 'canReview'));
    }
    public function category(Category $category): View { abort_unless($category->is_active,404);$products=$category->products()->where('is_active',true)->with('images')->latest()->paginate(12);return view('store.products',compact('products','category')); }
}
