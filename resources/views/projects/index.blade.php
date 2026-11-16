@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="flex flex-wrap items-center gap-3">
    <div class="mr-auto">
        <h1 class="page-title">Projects</h1>
        <p class="muted text-sm">Progress is rolled up automatically from each project's WBS.</p>
    </div>
    @can('projects.create')
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i data-lucide="plus" class="h-4 w-4"></i> New project
        </a>
    @endcan
</div>

<div class="card mt-6" x-data="tableFilter">
    <div class="card-header">
        <div class="relative w-full max-w-xs">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
            <input type="search" x-model="query" @input="filter" placeholder="Search on this page…" class="input pl-9">
        </div>
        <span class="muted ml-auto text-sm" x-show="visible !== null" x-text="visible + ' match(es)'" x-cloak></span>
    </div>

    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>PM</th>
                    <th>Status</th>
                    <th>Timeline</th>
                    <th class="w-48">Progress</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($projects as $project)
                <tr data-search="{{ $project->code }} {{ $project->name }} {{ $project->pic?->name }} {{ $project->status }}">
                    <td>
                        <a href="{{ route('projects.show', $project) }}" class="link">{{ $project->name }}</a>
                        <div class="muted text-xs">{{ $project->code ?? '-' }}</div>
                    </td>
                    <td>
                        @if ($project->pic)
                            <div class="flex items-center gap-2">
                                <x-avatar :user="$project->pic" size="h-7 w-7" />
                                <span class="whitespace-nowrap">{{ $project->pic->name }}</span>
                            </div>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td><x-status-badge :status="$project->status" /></td>
                    <td class="whitespace-nowrap text-xs">
                        @if ($project->start_date)
                            {{ \Carbon\Carbon::parse($project->start_date)->translatedFormat('d M Y') }}
                            <div class="muted">→ {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->translatedFormat('d M Y') : '-' }}</div>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td><x-progress :value="$project->progress" /></td>
                    <td>
                        <div class="flex justify-end gap-1">
                            @can('projects.report')
                                <a href="{{ route('projects.report', $project) }}" class="btn-icon" title="Export Excel">
                                    <i data-lucide="download" class="h-4 w-4"></i>
                                </a>
                            @endcan
                            <a href="{{ route('projects.show', $project) }}" class="btn-icon" title="Open">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            @can('projects.update')
                                <a href="{{ route('projects.edit', $project) }}" class="btn-icon" title="Edit">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </a>
                            @endcan
                            @can('projects.delete')
                                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                      data-confirm="Delete project {{ $project->name }} and its whole WBS?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-icon hover:!text-rose-600" title="Delete">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-empty-state icon="briefcase" title="No projects yet" description="Create the first project to start building its WBS." />
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($projects->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $projects->links() }}</div>
    @endif
</div>
@endsection
