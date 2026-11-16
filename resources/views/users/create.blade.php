@extends('layouts.app')

@section('title', 'New user')

@section('content')
<h1 class="page-title mb-6">New user</h1>

<form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('users._form')
</form>
@endsection
