@extends('layouts.app')

@section('title', 'Edit WBS item')

@section('content')
<div class="flex items-center gap-3">
    <a href="{{ route('projects.wbs', $wbs->project_id) }}" class="btn-icon" title="Back"><i data-lucide="arrow-left" class="h-5 w-5"></i></a>
    <h1 class="page-title">Edit WBS item</h1>
</div>

<form action="{{ route('wbs.update', $wbs->id) }}" method="POST" class="mt-6">
    @csrf
    @method('PUT')

    <div class="card max-w-2xl">
        <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="name">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $wbs->name) }}" class="input @error('name') input-error @enderror">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="code">Code</label>
                <input id="code" type="text" name="code" value="{{ old('code', $wbs->code) }}" class="input">
                @error('code')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="weight">Weight</label>
                <input id="weight" type="number" name="weight" value="{{ old('weight', $wbs->weight) }}" min="0" step="0.01" class="input">
                <p class="help">Relative to the other items on the same level.</p>
                @error('weight')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="progress">Progress (%)</label>
                @if ($isLeaf)
                    <input id="progress" type="number" name="progress" value="{{ old('progress', $wbs->progress) }}" min="0" max="100" step="0.01" class="input">
                @else
                    <input id="progress" type="number" value="{{ $wbs->progress }}" disabled class="input">
                    <p class="help">Calculated from the sub items (weighted average).</p>
                @endif
                @error('progress')<p class="error">{{ $message }}</p>@enderror
            </div>

            @if ($isLeaf)
                <div>
                    <label class="label" for="status">Status</label>
                    <select id="status" name="status" class="input">
                        @foreach (\App\Models\WbsItem::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $wbs->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="help">Left unchanged, it follows the progress (0% Open, 100% Complete).</p>
                    @error('status')<p class="error">{{ $message }}</p>@enderror
                </div>
            @endif

            <div class="sm:col-span-2">
                <label class="label" for="parent_id">Parent</label>
                <select id="parent_id" name="parent_id" class="input">
                    <option value="">— Root level —</option>
                    @foreach ($allWbs as $item)
                        <option value="{{ $item->id }}" @selected(old('parent_id', $wbs->parent_id) == $item->id)>{{ $item->code }} {{ $item->name }}</option>
                    @endforeach
                </select>
                @error('parent_id')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="start_date">Start date</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $wbs->start_date?->format('Y-m-d')) }}" class="input">
                @error('start_date')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="end_date">End date</label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $wbs->end_date?->format('Y-m-d')) }}" class="input">
                @error('end_date')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
            <a href="{{ route('projects.wbs', $wbs->project_id) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </div>
</form>
@endsection
