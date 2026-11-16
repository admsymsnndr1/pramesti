@extends('layouts.app')

@section('title', 'Roles & permissions')

@section('content')
<div class="flex flex-wrap items-center gap-3">
    <div class="mr-auto">
        <h1 class="page-title">Roles &amp; permissions</h1>
        <p class="muted text-sm">Menus and actions follow the permissions of each role.</p>
    </div>
    <a href="{{ route('roles.export') }}" class="btn btn-secondary">
        <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Export Excel
    </a>
    <a href="{{ route('roles.create') }}" class="btn btn-primary">
        <i data-lucide="shield-plus" class="h-4 w-4"></i> New role
    </a>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="table">
        <thead>
            <tr>
                <th>Role</th>
                <th>Code</th>
                <th>Description</th>
                <th class="text-center">Users</th>
                <th class="text-center">Permissions</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roles as $role)
            <tr>
                <td class="whitespace-nowrap font-medium text-slate-800 dark:text-slate-100">
                    {{ $role->display_name }}
                    @if ($role->is_system)
                        <span class="badge ml-1 bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">system</span>
                    @endif
                </td>
                <td><code class="text-xs">{{ $role->name }}</code></td>
                <td class="muted max-w-sm">{{ $role->description ?? '-' }}</td>
                <td class="text-center tabular-nums">{{ $role->users_count }}</td>
                <td class="text-center tabular-nums">
                    @if ($role->isSuperRole())
                        <span class="badge bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">All</span>
                    @else
                        {{ $role->permissions_count }} / {{ $totalPermissions }}
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('roles.edit', $role) }}" class="btn-icon" title="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                        @unless ($role->is_system)
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" data-confirm="Delete role {{ $role->display_name }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn-icon hover:!text-rose-600 disabled:opacity-30" title="{{ $role->users_count ? 'Still used by users' : 'Delete' }}"
                                        @disabled($role->users_count > 0)>
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        @endunless
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6"><x-empty-state icon="shield" title="No roles yet" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
