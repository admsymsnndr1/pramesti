@php $project ??= null; @endphp

<div class="card max-w-3xl">
    <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
        <div>
            <label class="label" for="code">Project code</label>
            <input id="code" type="text" name="code" class="input" placeholder="PRJ-001"
                   value="{{ old('code', $project?->code) }}">
        </div>

        <div>
            <label class="label" for="name">Project name <span class="text-rose-500">*</span></label>
            <input id="name" type="text" name="name" class="input @error('name') input-error @enderror" required
                   value="{{ old('name', $project?->name) }}">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label class="label" for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="input">{{ old('description', $project?->description) }}</textarea>
        </div>

        <div>
            <label class="label" for="start_date">Start date</label>
            <input id="start_date" type="date" name="start_date" class="input"
                   value="{{ old('start_date', $project?->start_date) }}">
        </div>

        <div>
            <label class="label" for="end_date">End date</label>
            <input id="end_date" type="date" name="end_date" class="input @error('end_date') input-error @enderror"
                   value="{{ old('end_date', $project?->end_date) }}">
            @error('end_date')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label" for="pm_id">Project manager</label>
            <select id="pm_id" name="pm_id" class="input">
                <option value="">— Select project manager —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('pm_id', $project?->pm_id) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label" for="status">Status</label>
            <select id="status" name="status" class="input">
                @foreach (['draft' => 'Draft', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'on_hold' => 'On hold'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $project?->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <a href="{{ $project ? route('projects.show', $project) : route('projects.index') }}" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary">Save project</button>
    </div>
</div>
