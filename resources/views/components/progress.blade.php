@props(['value' => 0, 'label' => true, 'height' => 'h-2'])

@php
    $value = max(0, min(100, (float) $value));
    $color = $value >= 100 ? 'bg-emerald-500' : ($value >= 50 ? 'bg-brand-500' : ($value > 0 ? 'bg-amber-500' : 'bg-slate-300'));
@endphp

<div {{ $attributes->class(['flex items-center gap-2']) }}>
    <div class="{{ $height }} w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
        <div class="{{ $height }} {{ $color }} rounded-full transition-all" style="width: {{ $value }}%"></div>
    </div>
    @if ($label)
        <span class="w-14 shrink-0 text-right text-xs font-medium tabular-nums text-slate-600 dark:text-slate-300">
            {{ rtrim(rtrim(number_format($value, 2), '0'), '.') }}%
        </span>
    @endif
</div>
