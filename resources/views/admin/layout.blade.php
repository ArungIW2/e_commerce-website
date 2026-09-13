<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - Store</title>
    <style>
        body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;background:#f5f7fb;color:#172033}.admin{display:flex;min-height:100vh}.sidebar{width:230px;background:#111827;color:#fff;padding:24px 16px;box-sizing:border-box}.sidebar h1{font-size:20px;margin:0 0 24px}.sidebar a{display:block;color:#d1d5db;text-decoration:none;padding:10px 12px;border-radius:8px;margin-bottom:5px}.sidebar a:hover,.sidebar a.active{background:#374151;color:#fff}.main{flex:1;padding:28px;max-width:1400px}.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 2px #00000008}.grid{display:grid;gap:16px}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}.btn{display:inline-block;border:0;border-radius:8px;padding:9px 14px;background:#2563eb;color:#fff;text-decoration:none;cursor:pointer}.btn.secondary{background:#6b7280}.btn.danger{background:#dc2626}.btn.small{padding:6px 10px;font-size:14px}.actions{display:flex;gap:8px;align-items:center}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.muted{color:#6b7280}.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;background:#e5e7eb}.badge.success{background:#dcfce7;color:#166534}.badge.warning{background:#fef3c7;color:#92400e}.badge.danger{background:#fee2e2;color:#991b1b}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:6px}.input,.textarea,.select{width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff}.textarea{min-height:130px;resize:vertical}.checkbox{display:flex;gap:8px;align-items:center}.alert{padding:12px 14px;border-radius:8px;background:#dcfce7;color:#166534;margin-bottom:16px}.errors{background:#fee2e2;color:#991b1b;padding:12px 14px;border-radius:8px;margin-bottom:16px}.search{display:flex;gap:8px;margin-bottom:16px}.search .input{max-width:320px}@media(max-width:800px){.sidebar{width:180px}.grid-3,.grid-2{grid-template-columns:1fr}.main{padding:16px}}
    </style>
</head>
<body>
<div class="admin">
    <aside class="sidebar">
        <h1>Store Admin</h1>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.products.index') }}">Products</a>
        <a href="{{ route('admin.categories.index') }}">Categories</a>
        <a href="{{ route('admin.orders.index') }}">Orders</a>
        <a href="{{ route('home') }}">View Store</a>
    </aside>
    <main class="main">
        <div class="topbar">
            <div><strong>{{ auth()->user()->name }}</strong><div class="muted">Administrator</div></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn secondary" type="submit">Logout</button></form>
        </div>
        @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
        @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>