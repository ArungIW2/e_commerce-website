@extends('store.layout')

@section('content')
<div class="mx-auto max-w-md px-4 py-12">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold">Buat akun</h1>
        <p class="mt-2 text-sm text-slate-500">Daftar sebagai pelanggan dan mulai berbelanja.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-medium">Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Konfirmasi password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-700">Daftar</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">Sudah punya akun? <a class="font-semibold text-slate-900" href="{{ route('login') }}">Masuk</a></p>
    </div>
</div>
@endsection
