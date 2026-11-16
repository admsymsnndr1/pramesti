@extends('layouts.app')

@section('title', $parent ? 'Add sub item' : 'Add WBS item')

@section('content')
<div class="flex items-center gap-3">
    <a href="{{ route('projects.wbs', $project_id) }}" class="btn-icon" title="Back"><i data-lucide="arrow-left" class="h-5 w-5"></i></a>
    <h1 class="page-title">{{ $parent ? 'Add sub item' : 'Add WBS item' }}</h1>
</div>

<form method="POST" action="{{ route('wbs.store') }}" data-ajax class="mt-6">
    @csrf
    <input type="hidden" name="project_id" value="{{ $project_id }}">
    <input type="hidden" name="parent_id" value="{{ $parent->id ?? '' }}">
    <input type="hidden" name="type" value="task">

    <div class="card max-w-2xl">
        <div class="space-y-5 p-6">
            @if ($parent)
                <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800/60">
                    <i data-lucide="corner-down-right" class="h-4 w-4 text-slate-400"></i>
                    Under <strong>{{ $parent->code }} {{ $parent->name }}</strong>
                </div>
            @endif

            <div>
                <label class="label" for="name">Name <span class="text-rose-500">*</span></label>
                <input id="name" type="text" name="name" class="input" required autofocus>
            </div>

            <div>
                <label class="label" for="weight">Weight <span class="text-rose-500">*</span></label>
                <input id="weight" type="number" name="weight" class="input" min="0" max="100" step="0.01" value="1" required>
                <p class="help">Relative to the other items on the same level, e.g. 3 phases weighted 20 / 50 / 30.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="start_date">Start date <span class="text-rose-500">*</span></label>
                    <input id="start_date" type="date" name="start_date" class="input" required
                           @if ($parent) min="{{ $parent->start_date?->format('Y-m-d') }}" max="{{ $parent->end_date?->format('Y-m-d') }}" @endif>
                </div>
                <div>
                    <label class="label" for="end_date">End date <span class="text-rose-500">*</span></label>
                    <input id="end_date" type="date" name="end_date" class="input" required
                           @if ($parent) min="{{ $parent->start_date?->format('Y-m-d') }}" max="{{ $parent->end_date?->format('Y-m-d') }}" @endif>
                </div>
            </div>
            @if ($parent && $parent->start_date)
                <p class="help -mt-3">Must fall within the parent: {{ $parent->start_date->translatedFormat('d M Y') }} – {{ $parent->end_date?->translatedFormat('d M Y') }}.</p>
            @endif
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
            <a href="{{ route('projects.wbs', $project_id) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </div>
</form>
@endsection
