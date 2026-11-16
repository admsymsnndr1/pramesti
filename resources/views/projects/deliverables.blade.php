@extends('layouts.app')

@section('title', 'Deliverables · ' . $project->name)

@section('content')
@include('projects._header')

<div class="card mt-6">
    <div class="card-header">
        <h2 class="card-title">Tasks with deliverables</h2>
    </div>

    @if ($wbsItems->isEmpty())
        <x-empty-state icon="package" title="No deliverables yet" description="Deliverables are uploaded per task by the assigned PIC." />
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Deliverables</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($wbsItems as $wbs)
                    <tr>
                        <td>
                            <div class="font-medium text-slate-800 dark:text-slate-100">{{ $wbs->name }}</div>
                            <div class="muted text-xs">{{ $wbs->code ?? '-' }}</div>
                        </td>
                        <td>{{ $wbs->deliverables->count() }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($wbs->deliverables->countBy('status') as $status => $count)
                                    <x-status-badge :status="$status" /> <span class="muted text-xs">×{{ $count }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('employee.tasks.show', $wbs) }}" class="btn btn-sm btn-secondary">
                                <i data-lucide="eye" class="h-3.5 w-3.5"></i> View
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
