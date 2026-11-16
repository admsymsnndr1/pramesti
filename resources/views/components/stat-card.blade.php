@props(['label', 'value', 'icon', 'tone' => 'brand'])

@php
    $tones = [
        'brand'   => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
        'sky'     => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'amber'   => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
    ];
@endphp

<div class="card flex items-center gap-4 p-5">
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] }}">
        <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
    </span>
    <div class="min-w-0">
        <div class="text-2xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ $value }}</div>
        <div class="muted truncate text-sm">{{ $label }}</div>
    </div>
</div>
