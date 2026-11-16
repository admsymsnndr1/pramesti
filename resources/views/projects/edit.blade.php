@extends('layouts.app')

@section('title', 'Edit project')

@section('content')
<h1 class="page-title mb-6">Edit project</h1>

<form action="{{ route('projects.update', $project) }}" method="POST">
    @csrf
    @method('PUT')
    @include('projects._form')
</form>
@endsection
