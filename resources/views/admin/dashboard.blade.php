@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
<div class="topbar"><div><h2>Dashboard</h2><p class="muted">Catalog, inventory, and order overview.</p></div></div>
<div class="grid grid-3">
<div class="card"><div class="muted">Products</div><div style="font-size:32px;font-weight:700">{{ $products }}</div></div>
<div class="card"><div class="muted">Categories</div><div style="font-size:32px;font-weight:700">{{ $categories }}</div></div>
<div class="card"><div class="muted">Customers</div><div style="font-size:32px;font-weight:700">{{ $customers }}</div></div>
<div class="card"><div class="muted">Orders</div><div style="font-size:32px;font-weight:700">{{ $orders }}</div></div>
<div class="card"><div class="muted">Pending Orders</div><div style="font-size:32px;font-weight:700">{{ $pendingOrders }}</div></div>
</div>
<div class="grid grid-2" style="margin-top:16px">
<div class="card"><div class="muted">Low Stock (1–5)</div><div style="font-size:28px;font-weight:700">{{ $lowStock }}</div><p class="muted">Products that need replenishment soon.</p></div>
<div class="card"><div class="muted">Out of Stock</div><div style="font-size:28px;font-weight:700">{{ $outOfStock }}</div><p class="muted">Products currently unavailable.</p></div>
</div>
<div class="card" style="margin-top:16px"><h3>Management</h3><div class="actions" style="margin-top:14px"><a class="btn" href="{{ route('admin.products.index') }}">Manage Products</a><a class="btn secondary" href="{{ route('admin.categories.index') }}">Manage Categories</a><a class="btn" href="{{ route('admin.orders.index') }}">Manage Orders</a></div></div>
@endsection