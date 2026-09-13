@extends('admin.layout')
@section('title', 'Categories')
@section('content')
<div class="topbar"><div><h2>Categories</h2><p class="muted">Manage your product categories.</p></div><a class="btn" href="{{ route('admin.categories.create') }}">+ Add Category</a></div>
<div class="card table-wrap">
<table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($categories as $category)
<tr><td><strong>{{ $category->name }}</strong><br><span class="muted">{{ $category->description ?: 'No description' }}</span></td><td>{{ $category->slug }}</td><td>{{ $category->products_count }}</td><td><span class="badge {{ $category->is_active ? 'success' : '' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td><td><div class="actions"><a class="btn small" href="{{ route('admin.categories.edit', $category) }}">Edit</a><form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? Products will become uncategorized.');">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form></div></td></tr>
@empty<tr><td colspan="5" class="muted">No categories found.</td></tr>@endforelse
</tbody></table></div>
<div style="margin-top:16px">{{ $categories->links() }}</div>
@endsection