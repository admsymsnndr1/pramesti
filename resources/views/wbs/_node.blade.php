@php
    $hasChildren = !empty($node->children);
    $start = $node->start_date ? \Carbon\Carbon::parse($node->start_date) : null;
    $end = $node->end_date ? \Carbon\Carbon::parse($node->end_date) : null;
@endphp

<li x-data="{ open: {{ $depth < 1 ? 'true' : 'false' }} }" @wbs-toggle-all.window="open = $event.detail">
    <div class="group flex flex-wrap items-center gap-y-2 px-5 py-2.5 hover:bg-slate-50 md:flex-nowrap dark:hover:bg-slate-800/40">
        {{-- name --}}
        <div class="flex min-w-0 basis-full items-center gap-2 md:basis-auto md:flex-1" style="padding-left: {{ $depth * 1.5 }}rem">
            @if ($hasChildren)
                <button type="button" @click="open = !open" class="btn-icon h-6 w-6" :aria-expanded="open">
                    <i data-lucide="chevron-right" class="h-4 w-4 transition-transform" :class="open && 'rotate-90'"></i>
                </button>
                <i data-lucide="folder" class="h-4 w-4 shrink-0 text-amber-500"></i>
                <span class="truncate font-medium text-slate-800 dark:text-slate-100" title="{{ $node->name }}">
                    <span class="muted mr-1 text-xs font-normal">{{ $node->code }}</span>{{ $node->name }}
                </span>
            @else
                <span class="w-6"></span>
                <i data-lucide="square-check-big" class="h-4 w-4 shrink-0 text-brand-500"></i>
                <a href="{{ route('employee.tasks.show', $node->id) }}" class="truncate hover:underline" title="{{ $node->name }}">
                    <span class="muted mr-1 text-xs">{{ $node->code }}</span>{{ $node->name }}
                </a>
            @endif
        </div>

        {{-- meta --}}
        <div class="muted w-24 text-right text-xs tabular-nums max-md:hidden">{{ rtrim(rtrim(number_format($node->weight, 2), '0'), '.') }}</div>
        <div class="muted w-44 text-right text-xs max-md:hidden">
            @if ($start && $end)
                {{ $start->translatedFormat('d M') }} – {{ $end->translatedFormat('d M Y') }}
            @else
                —
            @endif
        </div>
        <div class="w-28 text-right max-md:hidden">
            @unless ($hasChildren)
                <x-status-badge :status="$node->status" />
            @endunless
        </div>
        <div class="min-w-[8rem] flex-1 md:w-40 md:min-w-0 md:flex-none md:pl-4">
            <x-progress :value="$node->progress" />
        </div>

        {{-- actions --}}
        <div class="flex w-24 justify-end gap-0.5 opacity-100 md:opacity-0 md:group-hover:opacity-100 md:focus-within:opacity-100">
            @can('wbs.create')
                <a href="{{ route('wbs.create', ['parent_id' => $node->id, 'project_id' => $project->id]) }}" class="btn-icon h-7 w-7" title="Add sub item">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                </a>
            @endcan
            @can('wbs.update')
                <a href="{{ route('wbs.edit', $node->id) }}" class="btn-icon h-7 w-7" title="Edit">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                </a>
            @endcan
            @can('wbs.delete')
                <form method="POST" action="{{ route('wbs.destroy', $node->id) }}"
                      data-confirm="Delete &quot;{{ $node->name }}&quot;{{ $hasChildren ? ' and all of its sub items' : '' }}?">
                    @csrf
                    @method('DELETE')
                    <button class="btn-icon h-7 w-7 hover:!text-rose-600" title="Delete">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </form>
            @endcan
        </div>
    </div>

    @if ($hasChildren)
        <ul x-show="open" x-cloak class="divide-y divide-slate-100 border-t border-slate-100 dark:divide-slate-800 dark:border-slate-800">
            @foreach ($node->children as $child)
                @include('wbs._node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
