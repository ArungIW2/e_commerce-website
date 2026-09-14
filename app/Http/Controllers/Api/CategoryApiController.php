<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryApiController extends ApiController
{
    public function index()
    {
        return $this->success(Category::where('is_active', true)->orderBy('name')->get(['id','name','slug','description']));
    }

    public function show(Category $category)
    {
        if (!$category->is_active) return $this->error('Category not found.', 404);
        return $this->success($category->load(['products' => fn ($q) => $q->where('is_active', true)->orderBy('name')])->only(['id','name','slug','description','products']));
    }
}
