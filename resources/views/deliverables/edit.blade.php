@extends('layouts.app')

@section('title', 'Edit deliverable')

@section('content')
<h1 class="page-title mb-6">Edit deliverable</h1>

<form action="{{ route('deliverables.update', $deliverable) }}" method="POST" enctype="multipart/form-data" data-ajax>
    @csrf
    @method('PUT')
    @include('deliverables._form')
</form>
@endsection
