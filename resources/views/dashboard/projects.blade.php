@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex flex-wrap items-end gap-3">
    <div class="mr-auto">
        <h1 class="page-title">Hello, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</h1>
        <p class="muted text-sm">Here is where your projects stand today.</p>
    </div>
    @can('dashboard.admin_project')
        <a href="{{ route('dashboard.admin-project') }}" class="btn btn-secondary">
            <i data-lucide="bar-chart-3" class="h-4 w-4"></i> Project analytics
        </a>
    @endcan
</div>

{{-- KPIs --}}
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total projects" :value="$totalProjects" icon="briefcase" />
    <x-stat-card label="Ongoing" :value="$ongoingProjects" icon="activity" tone="sky" />
    <x-stat-card label="Completed" :value="$completedProjects" icon="check-circle-2" tone="emerald" />
    <x-stat-card label="Average task progress" :value="number_format($overallProgress, 1) . '%'" icon="trending-up" tone="amber" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- STATUS --}}
    <div class="card">
        <div class="card-header"><h2 class="card-title">Projects by status</h2></div>
        <div class="p-5">
            <div class="relative h-56"><canvas id="projectStatusChart"></canvas></div>
        </div>
    </div>

    {{-- TEAM --}}
    <div class="card">
        <div class="card-header"><h2 class="card-title">Team</h2><span class="muted ml-auto text-xs">{{ $teamRoles->sum('users_count') }} members</span></div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($teamRoles as $teamRole)
                <li class="flex items-center gap-3 px-5 py-3 text-sm">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800">
                        <i data-lucide="user-round" class="h-4 w-4"></i>
                    </span>
                    <span class="flex-1">{{ $teamRole->display_name }}</span>
                    <span class="font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ $teamRole->users_count }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- RECENT DELIVERABLES --}}
    <div class="card">
        <div class="card-header"><h2 class="card-title">Latest deliverables</h2></div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($recentDeliverables as $deliverable)
                <li class="px-5 py-3 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 truncate font-medium text-slate-800 dark:text-slate-100">{{ $deliverable->name }}</span>
                        <x-status-badge :status="$deliverable->status" />
                    </div>
                    <div class="muted mt-0.5 truncate text-xs">{{ $deliverable->uploader?->name }} · {{ $deliverable->created_at?->diffForHumans() }}</div>
                </li>
            @empty
                <li><x-empty-state icon="package" title="Nothing uploaded yet" /></li>
            @endforelse
        </ul>
    </div>
</div>

{{-- RECENT TASKS --}}
<div class="card mt-6 overflow-x-auto">
    <div class="card-header"><h2 class="card-title">Recently added tasks</h2></div>
    <table class="table">
        <thead>
            <tr><th>Task</th><th>Project</th><th>Status</th><th>Due</th><th class="w-48">Progress</th></tr>
        </thead>
        <tbody>
            @forelse ($recentTasks as $task)
                <tr>
                    <td><a href="{{ route('employee.tasks.show', $task) }}" class="link">{{ $task->name }}</a></td>
                    <td class="muted">{{ $task->project->name ?? '-' }}</td>
                    <td><x-status-badge :status="$task->status" /></td>
                    <td class="whitespace-nowrap">{{ $task->end_date?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td><x-progress :value="$task->progress" /></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty-state icon="inbox" title="No tasks yet" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const labels = { draft: 'Draft', ongoing: 'Ongoing', completed: 'Completed', on_hold: 'On hold' };
        const colors = { draft: '#94a3b8', ongoing: '#0ea5e9', completed: '#10b981', on_hold: '#f59e0b' };
        const data = @json($projectStatus);

        new Chart(document.getElementById('projectStatusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(data).map((key) => labels[key] ?? key),
                datasets: [{ data: Object.values(data), backgroundColor: Object.keys(data).map((key) => colors[key]), borderWidth: 0 }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
            },
        });
    });
</script>
@endpush
