@extends('layouts.app')

@section('title', $task->name)

@section('content')
<div class="flex flex-wrap items-start gap-3">
    <a href="{{ route('projects.wbs', $task->project_id) }}" class="btn-icon mt-0.5" title="Back to WBS"><i data-lucide="arrow-left" class="h-5 w-5"></i></a>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="page-title">{{ $task->name }}</h1>
            <x-status-badge :status="$task->status" />
        </div>
        <div class="muted mt-1 text-sm">{{ $task->code }} · under {{ $task->parent?->name ?? 'root' }}</div>
    </div>
    @can('wbs.update')
        <a href="{{ route('wbs.edit', $task->id) }}" class="btn btn-secondary"><i data-lucide="pencil" class="h-4 w-4"></i> Edit item</a>
    @endcan
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- ASSIGNMENT --}}
    <div class="card lg:col-span-2">
        <div class="card-header"><h2 class="card-title">Assignment</h2></div>

        @php $canEdit = auth()->user()->hasPermission('tasks.assign'); @endphp

        @if ($canEdit)
            <form action="{{ route('employee.tasks.assign', $task) }}" method="POST" class="space-y-5 p-6">
                @csrf
                <div>
                    <label class="label" for="pic_id">PIC</label>
                    <select id="pic_id" name="pic_id" class="input">
                        <option value="">— Not assigned —</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($task->pic_id === $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="description">Task description</label>
                    <textarea id="description" name="description" rows="6" class="input"
                              placeholder="Scope, acceptance criteria, notes…">{{ old('description', $task->description) }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        @else
            <div class="space-y-4 p-6 text-sm">
                <div>
                    <div class="muted text-xs">PIC</div>
                    <div class="font-medium">{{ $task->pic->name ?? 'Not assigned' }}</div>
                </div>
                <div>
                    <div class="muted text-xs">Description</div>
                    <div class="whitespace-pre-line">{{ $task->description ?: '-' }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- FACTS --}}
    <div class="card h-fit p-6">
        <h2 class="card-title">Progress</h2>
        <div class="mt-3 text-3xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ number_format($task->progress, 0) }}%</div>
        <x-progress :value="$task->progress" :label="false" class="mt-3" />

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="muted">Weight</dt><dd class="font-medium">{{ rtrim(rtrim(number_format($task->weight, 2), '0'), '.') }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Start</dt><dd class="font-medium">{{ $task->start_date?->translatedFormat('d M Y') ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">End</dt><dd class="font-medium">{{ $task->end_date?->translatedFormat('d M Y') ?? '-' }}</dd></div>
        </dl>
    </div>
</div>

@include('employee.tasks._deliverables')
@endsection
