@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">
        <i data-lucide="{{ $icon }}" class="h-6 w-6"></i>
    </span>
    <div class="mt-3 font-medium text-slate-700 dark:text-slate-200">{{ $title }}</div>
    @if ($description)
        <div class="muted mt-1 text-sm">{{ $description }}</div>
    @endif
    {{ $slot }}
</div>
