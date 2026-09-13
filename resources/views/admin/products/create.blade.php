@extends('admin.layout')
@section('title', 'Add Product')
@section('content')
<h2>Add Product</h2><div class="card"><form method="POST" action="{{ route('admin.products.store') }}">@csrf @include('admin.products.form')</form></div>
@endsection