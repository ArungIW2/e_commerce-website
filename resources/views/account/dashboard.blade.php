@extends('store.layout')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">
    <div class="rounded-2xl bg-slate-900 p-8 text-white">
        <p class="text-sm text-slate-300">Akun pelanggan</p>
        <h1 class="mt-2 text-3xl font-bold">Halo, {{ auth()->user()->name }} 👋</h1>
        <p class="mt-2 text-slate-300">Email: {{ auth()->user()->email }}</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mt-8 grid gap-5 md:grid-cols-3">
        <a href="{{ route('orders.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 hover:border-indigo-300"><h2 class="font-semibold">Pesanan</h2><p class="mt-2 text-sm text-slate-500">Lihat riwayat dan status pesanan.</p></a>
        <a href="{{ route('checkout') }}" class="rounded-xl border border-slate-200 bg-white p-6 hover:border-indigo-300"><h2 class="font-semibold">Alamat</h2><p class="mt-2 text-sm text-slate-500">Gunakan dan kelola alamat saat checkout.</p></a>
        <a href="{{ route('wishlist.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 hover:border-indigo-300"><h2 class="font-semibold">Wishlist</h2><p class="mt-2 text-sm text-slate-500">{{ auth()->user()->wishlistItems()->count() }} produk tersimpan.</p></a>
    </div>
</div>
@endsection
