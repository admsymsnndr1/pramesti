@extends('layouts.app')

@section('title', 'My tasks · ' . $project->name)

@section('content')
<div class="flex items-center gap-3">
    <a href="{{ route('employee.tasks.index') }}" class="btn-icon" title="Back"><i data-lucide="arrow-left" class="h-5 w-5"></i></a>
    <div>
        <h1 class="page-title">{{ $project->name }}</h1>
        <p class="muted text-sm">Your tasks in this project</p>
    </div>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Status</th>
                <th>Due</th>
                <th class="w-48">Progress</th>
                <th class="text-center">Deliverables</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($project->wbsItems as $task)
                @php $late = $task->end_date && $task->end_date->isPast() && $task->progress < 100; @endphp
                <tr>
                    <td>
                        <a href="{{ route('employee.tasks.detail-task', $task) }}" class="link">{{ $task->name }}</a>
                        <div class="muted text-xs">{{ $task->code }}</div>
                    </td>
                    <td><x-status-badge :status="$task->status" /></td>
                    <td class="whitespace-nowrap text-sm {{ $late ? 'font-medium text-rose-600' : '' }}">
                        {{ $task->end_date?->translatedFormat('d M Y') ?? '-' }}
                        @if ($late)<div class="text-xs">late</div>@endif
                    </td>
                    <td><x-progress :value="$task->progress" /></td>
                    <td class="text-center tabular-nums">{{ $task->deliverables->count() }}</td>
                    <td class="text-right">
                        <a href="{{ route('employee.tasks.detail-task', $task) }}" class="btn btn-sm btn-secondary">Open</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="clipboard" title="No tasks for you in this project" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
