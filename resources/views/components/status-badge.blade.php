@props(['status'])

@php
    $styles = [
        // project
        'draft'         => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'ongoing'       => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'completed'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'on_hold'       => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        // tasklist (WbsItem::STATUSES)
        'open'          => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        'on_progress'   => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'on_testing'    => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-300',
        'revisi_tester' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-300',
        'complete'      => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        // deliverable
        'submitted'     => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'approved'      => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    ];
    $label = \App\Models\WbsItem::STATUSES[$status] ?? \Illuminate\Support\Str::headline((string) $status);
@endphp

<span {{ $attributes->class(['badge', $styles[$status] ?? $styles['draft']]) }}>{{ $label }}</span>
