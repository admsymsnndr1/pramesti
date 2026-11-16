@extends('layouts.app')

@section('title', 'Timeline · ' . $project->name)

@section('content')
@include('projects._header')

@php
    // Gantt-style bars: position each task inside the project period
    $periodStart = $tasks->min('start_date') ?? ($project->start_date ? \Carbon\Carbon::parse($project->start_date) : null);
    $periodEnd = $tasks->max('end_date') ?? ($project->end_date ? \Carbon\Carbon::parse($project->end_date) : null);
    $periodDays = $periodStart && $periodEnd ? max(1, $periodStart->diffInDays($periodEnd) + 1) : null;
    $today = now()->startOfDay();
    $todayOffset = $periodDays && $today->between($periodStart, $periodEnd)
        ? $periodStart->diffInDays($today) / $periodDays * 100
        : null;
@endphp

<div class="card mt-6" x-data="tableFilter">
    <div class="card-header">
        <h2 class="card-title mr-auto">Task timeline</h2>
        <div class="relative w-full max-w-xs">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
            <input type="search" x-model="query" @input="filter" placeholder="Search task…" class="input pl-9">
        </div>
    </div>

    @if ($tasks->isEmpty())
        <x-empty-state icon="calendar-x" title="No dated tasks yet" description="Tasks appear here once they have a start and end date." />
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>PIC</th>
                        <th>Dates</th>
                        <th class="min-w-[280px]">
                            Schedule
                            @if ($periodDays)
                                <span class="ml-1 font-normal normal-case">
                                    ({{ $periodStart->translatedFormat('d M') }} – {{ $periodEnd->translatedFormat('d M Y') }})
                                </span>
                            @endif
                        </th>
                        <th class="w-40">Progress</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($tasks as $task)
                    @php
                        $isLate = $task->end_date && $task->end_date->lt($today) && $task->progress < 100;
                        $left = $periodDays && $task->start_date ? $periodStart->diffInDays($task->start_date) / $periodDays * 100 : 0;
                        $width = $periodDays && $task->start_date && $task->end_date
                            ? max(1, ($task->start_date->diffInDays($task->end_date) + 1) / $periodDays * 100) : 0;
                    @endphp
                    <tr data-search="{{ $task->code }} {{ $task->name }} {{ $task->pic?->name }}">
                        <td>
                            <div class="font-medium text-slate-800 dark:text-slate-100">{{ $task->name }}</div>
                            <div class="muted text-xs">{{ $task->code }}</div>
                        </td>
                        <td class="whitespace-nowrap">
                            @if ($task->pic)
                                <div class="flex items-center gap-2"><x-avatar :user="$task->pic" size="h-6 w-6" />{{ $task->pic->name }}</div>
                            @else
                                <span class="muted">Unassigned</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-xs">
                            @if ($task->start_date && $task->end_date)
                                {{ $task->start_date->translatedFormat('d M') }} – {{ $task->end_date->translatedFormat('d M Y') }}
                                <div class="{{ $isLate ? 'font-medium text-rose-600' : 'muted' }}">
                                    {{ $task->start_date->diffInDays($task->end_date) + 1 }} days{{ $isLate ? ' · late' : '' }}
                                </div>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="relative h-6 rounded bg-slate-100 dark:bg-slate-800">
                                @if ($todayOffset !== null)
                                    <div class="absolute inset-y-0 w-px bg-rose-400" style="left: {{ $todayOffset }}%" title="Today"></div>
                                @endif
                                @if ($width)
                                    <div class="absolute inset-y-1 overflow-hidden rounded {{ $isLate ? 'bg-rose-200 dark:bg-rose-500/30' : 'bg-brand-200 dark:bg-brand-500/30' }}"
                                         style="left: {{ $left }}%; width: {{ min($width, 100 - $left) }}%">
                                        <div class="h-full {{ $isLate ? 'bg-rose-500' : 'bg-brand-500' }}" style="width: {{ $task->progress }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td><x-progress :value="$task->progress" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
