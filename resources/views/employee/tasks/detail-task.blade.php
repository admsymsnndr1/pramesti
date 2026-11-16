@extends('layouts.app')

@section('title', $task->name)

@section('content')
<div class="flex flex-wrap items-start gap-3">
    <a href="{{ route('employee.tasks.detail', $task->project_id) }}" class="btn-icon mt-0.5" title="Back"><i data-lucide="arrow-left" class="h-5 w-5"></i></a>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="page-title">{{ $task->name }}</h1>
            <x-status-badge :status="$task->status" />
        </div>
        <div class="muted mt-1 text-sm">{{ $task->project->name }} · {{ $task->code }}</div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card p-6 lg:col-span-2">
        <h2 class="card-title">Description</h2>
        <div class="mt-3 whitespace-pre-line text-sm leading-relaxed">{{ $task->description ?: 'No description yet.' }}</div>
    </div>

    <div class="card h-fit p-6">
        <h2 class="card-title">Progress</h2>
        <div class="mt-3 text-3xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ number_format($task->progress, 0) }}%</div>
        <x-progress :value="$task->progress" :label="false" class="mt-3" />
        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="muted">PIC</dt><dd class="font-medium">{{ auth()->user()->name }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Start</dt><dd class="font-medium">{{ $task->start_date?->translatedFormat('d M Y') ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">End</dt><dd class="font-medium">{{ $task->end_date?->translatedFormat('d M Y') ?? '-' }}</dd></div>
        </dl>
    </div>
</div>

@include('employee.tasks._deliverables')
@endsection
