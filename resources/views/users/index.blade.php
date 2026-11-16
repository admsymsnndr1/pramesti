@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="flex flex-wrap items-center gap-3">
    <h1 class="page-title mr-auto">Users</h1>
    @can('users.create')
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i data-lucide="user-plus" class="h-4 w-4"></i> New user
        </a>
    @endcan
</div>

<div class="card mt-6" x-data="tableFilter">
    <div class="card-header">
        <div class="relative w-full max-w-xs">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
            <input type="search" x-model="query" @input="filter" placeholder="Search on this page…" class="input pl-9">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr data-search="{{ $user->name }} {{ $user->email }} {{ $user->userRole->display_name ?? $user->role }}">
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$user" />
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-100">{{ $user->name }}</div>
                                <div class="muted text-xs">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->userRole->display_name ?? $user->role }}</td>
                    <td>
                        @if ($user->active)
                            <span class="badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Active</span>
                        @else
                            <span class="badge bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex justify-end gap-1">
                            @if (auth()->user()->can('users.login_as') && !$user->is(auth()->user()) && !session('login_as_original_user'))
                                <form action="{{ route('users.loginAs', $user) }}" method="POST" data-confirm="View the app as {{ $user->name }}?">
                                    @csrf
                                    <button class="btn-icon" title="Login as this user"><i data-lucide="log-in" class="h-4 w-4"></i></button>
                                </form>
                            @endif
                            @can('users.update')
                                <a href="{{ route('users.edit', $user) }}" class="btn-icon" title="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                            @endcan
                            @if (auth()->user()->can('users.delete') && !$user->is(auth()->user()))
                                <form action="{{ route('users.destroy', $user) }}" method="POST" data-confirm="Delete {{ $user->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-icon hover:!text-rose-600" title="Delete"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $users->links() }}</div>
    @endif
</div>
@endsection
