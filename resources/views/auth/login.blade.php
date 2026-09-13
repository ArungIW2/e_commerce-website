@extends('store.layout')

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold">Masuk</h1>
        <p class="mt-2 text-sm text-slate-500">Masuk untuk melihat akun dan pesananmu.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1"> Ingat saya
            </label>
            <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-700">Masuk</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">Belum punya akun? <a class="font-semibold text-slate-900" href="{{ route('register') }}">Daftar</a></p>
    </div>
</div>
@endsection
