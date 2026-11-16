@extends('layouts.app')

@section('title', 'Edit user')

@section('content')
<h1 class="page-title mb-6">Edit user</h1>

<form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('users._form')
</form>
@endsection
