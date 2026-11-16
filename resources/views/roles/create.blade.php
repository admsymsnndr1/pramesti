@extends('layouts.app')

@section('title', 'New role')

@section('content')
<h1 class="page-title mb-6">New role</h1>

<form action="{{ route('roles.store') }}" method="POST">
    @csrf
    @include('roles._form')
</form>
@endsection
