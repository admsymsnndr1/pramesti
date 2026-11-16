@foreach (['success' => ['check-circle-2', 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20'],
           'error'   => ['alert-circle', 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20']] as $type => [$icon, $classes])
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="mb-5 flex items-center gap-3 rounded-lg px-4 py-3 text-sm ring-1 {{ $classes }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i>
            <span class="flex-1">{{ session($type) }}</span>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20">
        <div class="flex items-center gap-2 font-medium">
            <i data-lucide="alert-circle" class="h-4 w-4"></i> Please check the form:
        </div>
        <ul class="mt-1 list-disc pl-10">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
