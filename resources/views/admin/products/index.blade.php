@extends('admin.layout')
@section('title', 'Products')
@section('content')
<div class="topbar"><div><h2>Products</h2><p class="muted">Manage catalog, pricing and inventory.</p></div><a class="btn" href="{{ route('admin.products.create') }}">+ Add Product</a></div>
<form class="search" method="GET" action="{{ route('admin.products.index') }}"><input class="input" name="search" value="{{ request('search') }}" placeholder="Search name or SKU"><button class="btn secondary">Search</button></form>
<div class="card table-wrap"><table class="table"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($products as $product)
<tr><td><strong>{{ $product->name }}</strong><br><span class="muted">SKU: {{ $product->sku }}</span></td><td>{{ $product->category?->name ?? 'Uncategorized' }}</td><td>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td><td><span class="badge {{ $product->stock === 0 ? 'danger' : ($product->stock <= 5 ? 'warning' : 'success') }}">{{ $product->stock }} units</span></td><td><span class="badge {{ $product->is_active ? 'success' : '' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td><td><div class="actions"><a class="btn small" href="{{ route('admin.products.edit', $product) }}">Edit</a><form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?');">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form></div></td></tr>
@empty<tr><td colspan="6" class="muted">No products found.</td></tr>@endforelse
</tbody></table></div><div style="margin-top:16px">{{ $products->links() }}</div>
@endsection