@extends('layouts.app')

@section('title', 'My tasks')

@section('content')
<h1 class="page-title">My tasks</h1>
<p class="muted text-sm">Projects where you are the PIC of at least one task.</p>

<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($projects as $project)
        @php
            $mine = $project->wbsItems;
            $done = $mine->where('progress', '>=', 100)->count();
            $next = $mine->where('progress', '<', 100)->sortBy('end_date')->first();
        @endphp
        <a href="{{ route('employee.tasks.detail', $project) }}" class="card block p-5 transition hover:border-brand-300 hover:shadow-md dark:hover:border-brand-500/50">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ $project->name }}</div>
                    <div class="muted text-xs">{{ $project->code }}</div>
                </div>
                <x-status-badge :status="$project->status" />
            </div>

            <div class="mt-4 flex items-baseline gap-1">
                <span class="text-2xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ $done }}</span>
                <span class="muted text-sm">/ {{ $mine->count() }} tasks done</span>
            </div>
            <x-progress :value="$mine->count() ? $done / $mine->count() * 100 : 0" :label="false" class="mt-2" />

            @if ($next)
                <div class="muted mt-4 flex items-center gap-1.5 text-xs">
                    <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                    Next: <span class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $next->name }}</span>
                    · {{ $next->end_date?->translatedFormat('d M') }}
                </div>
            @endif
        </a>
    @empty
        <div class="card md:col-span-2 xl:col-span-3">
            <x-empty-state icon="clipboard-check" title="No tasks assigned to you" description="Tasks show up here once a PM assigns you as their PIC." />
        </div>
    @endforelse
</div>

@if ($projects->hasPages())
    <div class="mt-6">{{ $projects->links() }}</div>
@endif
@endsection
