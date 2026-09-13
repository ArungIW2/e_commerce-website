@extends('admin.layout')
@section('title', 'Edit Category')
@section('content')
<h2>Edit Category</h2><div class="card"><form method="POST" action="{{ route('admin.categories.update', $category) }}">@csrf @method('PUT') @include('admin.categories.form')</form></div>
@endsection