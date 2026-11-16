@props(['user', 'size' => 'h-9 w-9'])

@if ($user?->photo_url)
    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->class([$size, 'inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300']) }}>
        {{ $user?->initials ?: '?' }}
    </span>
@endif
