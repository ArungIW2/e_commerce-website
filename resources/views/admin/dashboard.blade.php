@extends('store.layout')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <p class="text-sm font-medium text-slate-500">Back Office</p>
            <h1 class="text-3xl font-bold">Admin Dashboard</h1>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm">{{ auth()->user()->name }}</span>
    </div>

    <div class="mt-8 grid gap-5 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Produk</p><p class="mt-2 text-3xl font-bold">{{ $products }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Kategori</p><p class="mt-2 text-3xl font-bold">{{ $categories }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Pelanggan</p><p class="mt-2 text-3xl font-bold">{{ $customers }}</p></div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="font-semibold">Modul berikutnya</h2>
        <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4">Product CRUD</div>
            <div class="rounded-lg bg-slate-50 p-4">Category CRUD</div>
            <div class="rounded-lg bg-slate-50 p-4">Inventory</div>
            <div class="rounded-lg bg-slate-50 p-4">Order Management</div>
        </div>
    </div>
</div>
@endsection
