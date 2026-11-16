@extends('layouts.app')

@section('title', 'New project')

@section('content')
<h1 class="page-title mb-6">New project</h1>

<form action="{{ route('projects.store') }}" method="POST">
    @csrf
    @include('projects._form')
</form>
@endsection
