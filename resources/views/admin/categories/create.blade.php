@extends('admin.layout')
@section('title', 'Add Category')
@section('content')
<h2>Add Category</h2><div class="card"><form method="POST" action="{{ route('admin.categories.store') }}">@csrf @include('admin.categories.form')</form></div>
@endsection