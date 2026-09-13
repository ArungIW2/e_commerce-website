@extends('admin.layout')
@section('title', 'Edit Product')
@section('content')
<h2>Edit Product</h2><div class="card"><form method="POST" action="{{ route('admin.products.update', $product) }}">@csrf @method('PUT') @include('admin.products.form')</form></div>
@endsection