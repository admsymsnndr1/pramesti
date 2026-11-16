@php
    $deliverable ??= null;
    $cancelUrl = auth()->user()->hasPermission('wbs.view')
        ? route('employee.tasks.show', $task)
        : route('employee.tasks.detail-task', $task);
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="space-y-5 p-6">
            <div>
                <label class="label" for="name">Name <span class="text-rose-500">*</span></label>
                <input id="name" type="text" name="name" class="input" placeholder="e.g. Final report document"
                       value="{{ old('name', $deliverable?->name) }}" required>
            </div>

            <div>
                <label class="label" for="description">Description</label>
                <textarea id="description" name="description" rows="4" class="input"
                          placeholder="What does this deliverable contain?">{{ old('description', $deliverable?->description) }}</textarea>
            </div>

            <div>
                <label class="label" for="files">Files</label>
                <input id="files" name="files[]" type="file" multiple
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                       class="block w-full rounded-lg border border-dashed border-slate-300 p-3 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 dark:border-slate-700 dark:file:bg-brand-500/15 dark:file:text-brand-300">
                <p class="help">JPG, PNG, PDF, Word, Excel or PowerPoint, up to 10 MB each.</p>

                @if ($deliverable?->files->isNotEmpty())
                    <ul class="mt-3 space-y-1 text-sm">
                        @foreach ($deliverable->files as $file)
                            <li class="flex items-center gap-2">
                                <i data-lucide="paperclip" class="h-3.5 w-3.5 text-slate-400"></i>
                                <a href="{{ $file->file_url }}" target="_blank" rel="noopener" class="link truncate">{{ $file->file_name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <label class="label" for="url">Link</label>
                <input id="url" type="url" name="url" class="input" placeholder="https://…"
                       value="{{ old('url', $deliverable?->url) }}">
                <p class="help">For deliverables that live elsewhere (Google Drive, Figma, a repository…).</p>
            </div>
        </div>
    </div>

    <div class="card h-fit p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Task</div>
        <div class="mt-1 font-medium text-slate-800 dark:text-slate-100">{{ $task->name }}</div>
        <div class="muted text-xs">{{ $task->code }}</div>

        @if ($deliverable)
            <div class="mt-5">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="input">
                    <option value="draft" @selected(old('status', $deliverable->status) === 'draft')>Draft</option>
                    <option value="submitted" @selected(old('status', $deliverable->status) === 'submitted')>Submitted</option>
                    @if (auth()->user()->hasPermission('deliverables.approve') || $deliverable->status === 'approved')
                        <option value="approved" @selected(old('status', $deliverable->status) === 'approved')>Approved</option>
                    @endif
                </select>
            </div>
        @endif

        <div class="mt-6 flex gap-2">
            <a href="{{ $cancelUrl }}" class="btn btn-secondary flex-1">Cancel</a>
            <button type="submit" class="btn btn-primary flex-1">Save</button>
        </div>
    </div>
</div>
