<?php
namespace App\Http\Controllers\Api;
use App\Http\Resources\ProductResource; use App\Models\Category; use Illuminate\Http\Request;
class CategoryApiController extends ApiController { public function index(){return $this->success(Category::where('is_active',true)->orderBy('name')->get(['id','name','slug','description']));} public function show(Category $category,Request $request){if(!$category->is_active)return $this->error('Category not found.',404);$products=$category->products()->with('category')->where('is_active',true)->orderBy('name')->paginate(min(max((int)$request->input('per_page',15),1),50));return $this->success(['category'=>$category->only(['id','name','slug','description']),'products'=>ProductResource::collection($products)]);} }
