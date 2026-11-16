{{-- deliverable list of a task, shared by the WBS task page and the "my task" page --}}
<div class="card mt-6">
    <div class="card-header">
        <h2 class="card-title mr-auto">Deliverables</h2>
        @can('deliverables.create')
            <a href="{{ route('deliverables.create', $task) }}" class="btn btn-sm btn-primary">
                <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add deliverable
            </a>
        @endcan
    </div>

    @if ($task->deliverables->isEmpty())
        <x-empty-state icon="package-open" title="No deliverables yet" description="Upload the result of this task so it can be reviewed." />
    @else
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($task->deliverables as $deliverable)
                <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-slate-800 dark:text-slate-100">{{ $deliverable->name }}</span>
                            <x-status-badge :status="$deliverable->status" />
                        </div>
                        <div class="muted mt-0.5 text-xs">
                            by {{ $deliverable->uploader?->name ?? '-' }} · {{ $deliverable->created_at?->diffForHumans() }}
                        </div>
                        @if ($deliverable->description)
                            <p class="mt-2 text-sm">{{ $deliverable->description }}</p>
                        @endif

                        <div class="mt-2 flex flex-wrap gap-2">
                            @if ($deliverable->url)
                                <a href="{{ $deliverable->url }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">
                                    <i data-lucide="link" class="h-3.5 w-3.5"></i> Open link
                                </a>
                            @endif
                            @foreach ($deliverable->files as $file)
                                <a href="{{ $file->file_url }}" target="_blank" rel="noopener"
                                   class="inline-flex max-w-xs items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">
                                    <i data-lucide="{{ in_array($file->file_type, ['jpg', 'jpeg', 'png']) ? 'image' : 'file-text' }}" class="h-3.5 w-3.5"></i>
                                    <span class="truncate">{{ $file->file_name }}</span>
                                    <span class="muted">{{ number_format($file->file_size / 1024, 0) }} KB</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-1">
                        @can('deliverables.update')
                            <a href="{{ route('deliverables.edit', $deliverable) }}" class="btn-icon" title="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                        @endcan
                        @can('deliverables.delete')
                            <form action="{{ route('deliverables.destroy', $deliverable) }}" method="POST" data-confirm="Delete this deliverable and its files?">
                                @csrf
                                @method('DELETE')
                                <button class="btn-icon hover:!text-rose-600" title="Delete"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                            </form>
                        @endcan
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
