@extends('layouts.app')

@section('title', 'Project analytics')

@section('content')
@php
    $statusColors = [
        'open'          => '#f43f5e',
        'on_progress'   => '#f59e0b',
        'on_testing'    => '#d946ef',
        'revisi_tester' => '#f97316',
        'complete'      => '#10b981',
    ];
    $statusFilters = ['ongoing' => 'Ongoing', 'draft' => 'Draft', 'on_hold' => 'On hold', 'completed' => 'Completed', 'all' => 'All'];
    $fmt = fn ($value) => number_format((float) $value, 2) . '%';
    $sections = [
        'scurve'   => ['S-curve', 'Planned vs actual cumulative progress'],
        'trend'    => ['Completed tasks', 'Tasks in "Complete" status per day'],
        'wbs'      => ['Deepest WBS level', 'Progress of the lowest WBS level per day'],
        'progress' => ['Progress per WBS level', 'Weighted progress of every level today'],
    ];
@endphp

<div class="flex flex-wrap items-end gap-3">
    <div class="mr-auto max-w-2xl">
        <h1 class="page-title">Project analytics</h1>
        <p class="muted text-sm">
            Tasks are the leaf items of each WBS. Today's numbers are calculated live; earlier days come from the
            daily snapshot
            @if ($firstSnapshot)
                (recorded since {{ $firstSnapshot->translatedFormat('d M Y') }}).
            @else
                (no snapshot yet, run <code>php artisan dashboard:snapshot</code>).
            @endif
        </p>
    </div>
    <form method="GET" action="{{ route('dashboard.admin-project') }}" class="flex items-center gap-2">
        <label for="status" class="muted text-sm">Project status</label>
        <select id="status" name="status" class="input w-36" onchange="this.form.submit()">
            @foreach ($statusFilters as $value => $label)
                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

@if ($projects->isEmpty())
    <div class="card mt-6"><x-empty-state icon="bar-chart-3" title="No projects with this status" /></div>
@else

{{-- TASKLIST + SUMMARY --}}
<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
    <div class="card xl:col-span-2">
        <div class="card-header"><h2 class="card-title">Tasks by status</h2></div>
        <div class="p-5"><div class="relative h-80"><canvas id="tasklist-report"></canvas></div></div>
    </div>

    <div class="card overflow-x-auto xl:col-span-3">
        <div class="card-header"><h2 class="card-title">Progress summary</h2></div>
        <table class="table">
            <thead>
                <tr>
                    <th>Project</th>
                    @foreach ($statuses as $key => $label)
                        <th class="text-center">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $statusColors[$key] }}"></span>{{ $label }}
                            </span>
                        </th>
                    @endforeach
                    <th class="text-center">Total</th>
                    <th class="text-right">Progress</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td><a href="{{ route('projects.wbs', $project['id']) }}" class="link whitespace-nowrap">{{ $project['name'] }}</a></td>
                        @foreach ($statuses as $key => $label)
                            <td class="text-center tabular-nums">{{ $project['counts'][$key] }}</td>
                        @endforeach
                        <td class="text-center font-semibold tabular-nums">{{ $project['total'] }}</td>
                        <td class="whitespace-nowrap text-right tabular-nums">{{ $fmt($project['progress']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    @foreach ($statuses as $key => $label)
                        <td class="text-center tabular-nums">{{ $projects->sum(fn ($p) => $p['counts'][$key]) }}</td>
                    @endforeach
                    <td class="text-center tabular-nums">{{ $projects->sum('total') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- PER PROJECT CHARTS --}}
@foreach ($sections as $section => [$sectionTitle, $sectionHint])
    <div class="mt-10 flex items-baseline gap-3">
        <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">{{ $sectionTitle }}</h2>
        <span class="muted text-sm">{{ $sectionHint }}</span>
    </div>
    <div class="mt-3 grid grid-cols-1 gap-6 xl:grid-cols-2">
        @foreach ($projects as $project)
            <div class="card p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-medium text-slate-800 dark:text-slate-100">{{ $project['name'] }}</h3>
                    <span class="muted text-xs">
                        @switch($section)
                            @case('wbs') Level {{ $project['deepest_level'] ?? '-' }} @break
                            @case('scurve')
                                @if ($project['scurve'] && $project['scurve']['today_plan'] !== null)
                                    @php $deviation = $project['progress'] - $project['scurve']['today_plan']; @endphp
                                    Plan {{ $fmt($project['scurve']['today_plan']) }} · Actual {{ $fmt($project['progress']) }} ·
                                    <span class="font-semibold {{ $deviation < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $deviation >= 0 ? '+' : '' }}{{ $fmt($deviation) }}
                                    </span>
                                @endif
                                @break
                        @endswitch
                    </span>
                </div>
                <div class="relative mt-3 h-64">
                    @if ($section === 'scurve' && !$project['scurve'])
                        <div class="muted flex h-full items-center justify-center text-sm">Project start/end date not set.</div>
                    @elseif ($section !== 'trend' && $section !== 'scurve' && !$project['deepest_level'])
                        <div class="muted flex h-full items-center justify-center text-sm">No WBS yet.</div>
                    @else
                        <canvas id="chart-{{ $section }}-{{ $project['id'] }}"></canvas>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endforeach

@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const projects = @json($projects);
    const statuses = @json($statuses);
    const statusColors = @json($statusColors);
    if (!projects.length) return;

    const pct = v => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';

    // value labels above points / inside stacked bars (options.plugins.valueLabels.format)
    const valueLabels = {
        id: 'valueLabels',
        afterDatasetsDraw(chart, args, opts) {
            if (!opts || !opts.format) return;
            const { ctx } = chart;
            const stacked = !!(chart.options.scales.y && chart.options.scales.y.stacked);
            ctx.save();
            ctx.font = '600 11px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            chart.data.datasets.forEach((ds, i) => {
                const meta = chart.getDatasetMeta(i);
                if (meta.hidden || ds.noLabels) return;
                meta.data.forEach((el, j) => {
                    const v = ds.data[j];
                    if (v === null || v === undefined || (stacked && !v)) return;
                    const p = el.tooltipPosition();
                    if (stacked) {
                        ctx.fillStyle = '#fff';
                        ctx.fillText(opts.format(v), p.x, (el.y + el.base) / 2);
                    } else {
                        ctx.fillStyle = ds.borderColor;
                        ctx.fillText(opts.format(v), p.x, p.y - 10);
                    }
                });
            });
            ctx.restore();
        },
    };

    const make = (id, config) => {
        const el = document.getElementById(id);
        if (el) new Chart(el, Object.assign({ plugins: [valueLabels] }, config));
    };
    const percentAxis = { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } };

    // tasks per status, all projects (stacked)
    make('tasklist-report', {
        type: 'bar',
        data: {
            labels: projects.map(p => p.name),
            datasets: Object.keys(statuses).map(key => ({
                label: statuses[key],
                data: projects.map(p => p.counts[key]),
                backgroundColor: statusColors[key],
                borderRadius: 4,
            })),
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } }, valueLabels: { format: v => v } },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { autoSkip: false, maxRotation: 0,
                    callback(value) { const l = this.getLabelForValue(value); return l.length > 16 ? l.match(/.{1,16}(\s|$)/g) : l; } } },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });

    projects.forEach(p => {
        if (p.scurve) {
            make('chart-scurve-' + p.id, {
                type: 'line',
                data: {
                    labels: p.scurve.labels,
                    datasets: [
                        { label: 'Plan', data: p.scurve.plan, borderColor: '#94a3b8', backgroundColor: '#94a3b8',
                          borderDash: [6, 4], pointRadius: 0, tension: 0.25 },
                        { label: 'Actual', data: p.scurve.actual, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)',
                          pointRadius: 2, spanGaps: true, fill: true },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { callbacks: { label: c => c.raw === null ? null : c.dataset.label + ': ' + pct(c.raw) } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 7, maxRotation: 0, autoSkipPadding: 16 } },
                        y: percentAxis,
                    },
                },
            });
        }

        make('chart-trend-' + p.id, {
            type: 'line',
            data: {
                labels: p.trend.labels,
                datasets: [{ label: 'Complete', data: p.trend.complete, borderColor: '#6366f1', backgroundColor: '#6366f1', pointRadius: 3 }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } },
                          y: { beginAtZero: true, grace: '15%', ticks: { precision: 0 } } },
            },
        });

        make('chart-wbs-' + p.id, {
            type: 'line',
            data: {
                labels: p.trend.labels,
                datasets: [{ label: 'WBS level ' + p.deepest_level, data: p.trend.wbs, borderColor: '#f97316', backgroundColor: '#f97316', pointRadius: 3, spanGaps: true }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + pct(c.raw) } } },
                scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } }, y: percentAxis },
            },
        });

        const levels = Object.keys(p.level_progress);
        make('chart-progress-' + p.id, {
            type: 'bar',
            data: {
                labels: levels.map(l => 'Level ' + l),
                datasets: [{ label: 'Progress', data: levels.map(l => p.level_progress[l]),
                    backgroundColor: '#6366f1', borderColor: '#6366f1', borderRadius: 6, maxBarThickness: 56 }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, valueLabels: { format: pct }, tooltip: { callbacks: { label: c => pct(c.raw) } } },
                scales: { x: { grid: { display: false } }, y: Object.assign({ grace: '10%' }, percentAxis) },
            },
        });
    });
});
</script>
@endpush
