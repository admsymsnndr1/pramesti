@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="page-title">Hello, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</h1>
<p class="muted text-sm">Your tasks across all projects.</p>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Projects" :value="$assignedProjects" icon="layers" />
    <x-stat-card label="Tasks assigned" :value="$assignedTasks" icon="list-checks" tone="sky" />
    <x-stat-card label="Tasks done" :value="$completedTasks" icon="check-circle-2" tone="emerald" />
    <x-stat-card label="Average progress" :value="number_format($avgProgress, 1) . '%'" icon="activity" tone="amber" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Done vs remaining</h2></div>
        <div class="p-5"><div class="relative h-64"><canvas id="taskStatusChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Tasks per project</h2></div>
        <div class="p-5"><div class="relative h-64"><canvas id="taskByProjectChart"></canvas></div></div>
    </div>
</div>

<div class="card mt-6 overflow-x-auto">
    <div class="card-header">
        <h2 class="card-title mr-auto">Latest tasks</h2>
        <a href="{{ route('employee.tasks.index') }}" class="link text-sm">All my tasks →</a>
    </div>
    <table class="table">
        <thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Due</th><th class="w-48">Progress</th></tr></thead>
        <tbody>
            @forelse ($recentTasks as $task)
                <tr>
                    <td><a href="{{ route('employee.tasks.detail-task', $task) }}" class="link">{{ $task->name }}</a></td>
                    <td class="muted">{{ $task->project->name ?? '-' }}</td>
                    <td><x-status-badge :status="$task->status" /></td>
                    <td class="whitespace-nowrap">{{ $task->end_date?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td><x-progress :value="$task->progress" /></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty-state icon="clipboard-check" title="No tasks assigned to you yet" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const status = @json($taskStatusChart);
        new Chart(document.getElementById('taskStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Done', 'Remaining'],
                datasets: [{ data: [status['Completed'], status['On Progress']], backgroundColor: ['#10b981', '#f59e0b'], borderWidth: 0 }],
            },
            options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } } },
        });

        const perProject = @json($tasksByProject);
        new Chart(document.getElementById('taskByProjectChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(perProject),
                datasets: [{ label: 'Tasks', data: Object.values(perProject), backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 48 }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
            },
        });
    });
</script>
@endpush
