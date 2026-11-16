@extends('layouts.app')

@section('title', 'WBS')

@section('content')
<h1 class="page-title">Work Breakdown Structure</h1>
<p class="muted text-sm">Pick a project to open its WBS.</p>

<div class="card mt-6 overflow-x-auto">
    <table class="table">
        <thead>
            <tr>
                <th>Project</th>
                <th>Status</th>
                <th>Timeline</th>
                <th class="text-center">Items</th>
                <th class="w-48">Progress</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($projects as $project)
            <tr>
                <td>
                    <a href="{{ route('projects.wbs', $project) }}" class="link">{{ $project->name }}</a>
                    <div class="muted text-xs">{{ $project->code ?? '-' }}</div>
                </td>
                <td><x-status-badge :status="$project->status" /></td>
                <td class="whitespace-nowrap text-xs">
                    {{ $project->start_date ?? '-' }}
                    <div class="muted">→ {{ $project->end_date ?? '-' }}</div>
                </td>
                <td class="text-center">{{ $project->wbs_items_count }}</td>
                <td><x-progress :value="$project->progress" /></td>
                <td class="text-right">
                    <a href="{{ route('projects.wbs', $project) }}" class="btn btn-sm btn-secondary">Open</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state icon="briefcase" title="No projects yet" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
