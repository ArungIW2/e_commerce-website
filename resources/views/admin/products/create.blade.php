@extends('admin.layout')
@section('content')<div class="mx-auto max-w-5xl px-6 py-10"><h1 class="mb-8 text-3xl font-black">Create Product</h1>@include('admin.products._form',['action'=>route('admin.products.store'),'method'=>'POST'])</div>@endsection
