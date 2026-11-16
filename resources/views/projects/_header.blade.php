{{-- project title + tabs, shared by overview / WBS / timeline / deliverables --}}
@php
    $tabs = [
        'show'         => ['Overview', 'layout-grid', 'projects.view'],
        'wbs'          => ['WBS', 'git-branch', 'wbs.view'],
        'timeline'     => ['Timeline', 'calendar-range', 'projects.view'],
        'deliverables' => ['Deliverables', 'package', 'projects.view'],
    ];
    $backUrl = auth()->user()->can('projects.view') ? route('projects.index') : route('wbs.index');
@endphp

<div class="flex flex-wrap items-start gap-4">
    <a href="{{ $backUrl }}" class="btn-icon mt-0.5" title="Back">
        <i data-lucide="arrow-left" class="h-5 w-5"></i>
    </a>
    <div class="min-w-[12rem] flex-1">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="page-title">{{ $project->name }}</h1>
            <x-status-badge :status="$project->status" />
        </div>
        <div class="muted mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><i data-lucide="hash" class="h-3.5 w-3.5"></i>{{ $project->code ?? 'No code' }}</span>
            @if ($project->start_date && $project->end_date)
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                    {{ \Carbon\Carbon::parse($project->start_date)->translatedFormat('d M Y') }}
                    – {{ \Carbon\Carbon::parse($project->end_date)->translatedFormat('d M Y') }}
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><i data-lucide="user" class="h-3.5 w-3.5"></i>{{ $project->pic->name ?? 'No PM' }}</span>
        </div>
    </div>
    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
        @can('projects.report')
            <a href="{{ route('projects.report', $project) }}" class="btn btn-secondary">
                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Export Excel
            </a>
        @endcan
        @can('projects.update')
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">
                <i data-lucide="pencil" class="h-4 w-4"></i> Edit
            </a>
        @endcan
    </div>
</div>

<nav class="mt-6 flex gap-6 overflow-x-auto overflow-y-hidden border-b [scrollbar-width:none] border-slate-200 dark:border-slate-800">
    @foreach ($tabs as $key => [$label, $icon, $permission])
        @continue(!auth()->user()->can($permission))
        <a href="{{ route('projects.' . $key, $project) }}"
           class="tab inline-flex items-center gap-2 {{ $tab === $key ? 'tab-active' : '' }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $label }}
        </a>
    @endforeach
</nav>
