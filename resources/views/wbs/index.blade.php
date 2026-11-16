@extends('layouts.app')

@section('title', 'WBS · ' . $project->name)

@section('content')
@include('projects._header')

<div class="card mt-6" x-data="{ expanded: true }">
    <div class="card-header flex-wrap">
        <div class="mr-auto">
            <h2 class="card-title">Work Breakdown Structure</h2>
            <p class="muted text-xs">Parent progress = weighted average of its children. Only leaf items are updated by hand.</p>
        </div>
        @unless (empty($tree))
            <button type="button" class="btn btn-sm btn-secondary"
                    @click="expanded = !expanded; $dispatch('wbs-toggle-all', expanded)">
                <i data-lucide="chevrons-up-down" class="h-3.5 w-3.5"></i>
                <span x-text="expanded ? 'Collapse all' : 'Expand all'"></span>
            </button>
        @endunless
        @can('wbs.create')
            <a href="{{ route('wbs.create', ['project_id' => $project->id]) }}" class="btn btn-sm btn-primary">
                <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add item
            </a>
        @endcan
    </div>

    @if (empty($tree))
        <x-empty-state icon="git-branch" title="No WBS yet" description="Break the project into phases, then into tasks that can be assigned.">
            @can('wbs.create')
                <a href="{{ route('wbs.create', ['project_id' => $project->id]) }}" class="btn btn-primary mt-4">
                    <i data-lucide="plus" class="h-4 w-4"></i> Add the first item
                </a>
            @endcan
        </x-empty-state>
    @else
        {{-- column headings (desktop) --}}
        <div class="hidden border-b border-slate-200 bg-slate-50 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 md:flex dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex-1">Item</div>
            <div class="w-24 text-right">Weight</div>
            <div class="w-44 text-right">Dates</div>
            <div class="w-28 text-right">Status</div>
            <div class="w-40 pl-4">Progress</div>
            <div class="w-24"></div>
        </div>

        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($tree as $node)
                @include('wbs._node', ['node' => $node, 'depth' => 0])
            @endforeach
        </ul>
    @endif
</div>
@endsection
