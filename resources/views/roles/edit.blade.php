@extends('layouts.app')

@section('title', 'Edit role')

@section('content')
<h1 class="page-title mb-6">Edit role: {{ $role->display_name }}</h1>

<form action="{{ route('roles.update', $role) }}" method="POST">
    @csrf
    @method('PUT')
    @include('roles._form')
</form>
@endsection
