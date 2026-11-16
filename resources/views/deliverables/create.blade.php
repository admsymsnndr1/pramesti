@extends('layouts.app')

@section('title', 'New deliverable')

@section('content')
<h1 class="page-title mb-6">New deliverable</h1>

<form action="{{ route('deliverables.store', $task) }}" method="POST" enctype="multipart/form-data" data-ajax>
    @csrf
    @include('deliverables._form')
</form>
@endsection
