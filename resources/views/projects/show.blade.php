@extends('layouts.app')

@section('title', $project->name)

@section('content')
@include('projects._header')

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- PROGRESS --}}
    <div class="card p-6 lg:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="card-title">Progress</h2>
            <span class="muted text-xs">Weighted roll-up of the WBS</span>
        </div>
        <div class="mt-4 flex items-end gap-3">
            <span class="text-4xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">
                {{ number_format($progress, 2) }}%
            </span>
        </div>
        <x-progress :value="$progress" :label="false" height="h-3" class="mt-4" />

        @if ($project->description)
            <p class="muted mt-6 text-sm leading-relaxed">{{ $project->description }}</p>
        @endif
    </div>

    {{-- INFO --}}
    <div class="card p-6">
        <h2 class="card-title">Details</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="muted">Project manager</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $project->pic->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="muted">Created by</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $project->creator->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="muted">Duration</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-100">
                    @if ($project->start_date && $project->end_date)
                        {{ \Carbon\Carbon::parse($project->start_date)->diffInDays($project->end_date) + 1 }} days
                    @else
                        -
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    {{-- SCOPE --}}
    <x-stat-card label="WBS items" :value="$stats['total_wbs']" icon="git-branch" />
    <x-stat-card label="Tasks (leaf items)" :value="$stats['leaf_wbs']" icon="check-square" tone="sky" />
    <x-stat-card label="Deliverables" :value="$stats['deliverables']" icon="package" tone="emerald" />
</div>
@endsection
