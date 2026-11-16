<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ sidebar: false }">
@php
    $user = auth()->user();
    $menu = \App\Main\SideMenu::for($user);
@endphp

{{-- mobile backdrop --}}
<div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false"
     class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

{{-- SIDEBAR --}}
<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform lg:translate-x-0"
       :class="sidebar && 'translate-x-0'">
    <a href="{{ route('dashboard') }}" class="flex h-16 items-center gap-3 px-5">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
            <i data-lucide="kanban-square" class="h-5 w-5"></i>
        </span>
        <span class="leading-tight">
            <span class="block text-base font-semibold text-white">{{ config('app.name') }}</span>
            <span class="block text-xs text-slate-400">Project Management</span>
        </span>
    </a>

    <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-3">
        @foreach ($menu as $item)
            <a href="{{ route($item['route']) }}"
               class="nav-link {{ request()->routeIs($item['active']) ? 'nav-link-active' : '' }}">
                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                {{ $item['title'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4 text-xs text-slate-500">
        Signed in as <span class="text-slate-300">{{ $user->userRole->display_name ?? $user->role }}</span>
    </div>
</aside>

{{-- MAIN --}}
<div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6 dark:border-slate-800 dark:bg-slate-900/80">
        <button type="button" class="btn-icon lg:hidden" @click="sidebar = true" aria-label="Open menu">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>

        <nav aria-label="Breadcrumb" class="hidden min-w-0 items-center gap-2 text-sm sm:flex">
            <span class="muted">{{ config('app.name') }}</span>
            @isset($breadcrumb)
                <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-400"></i>
                <span class="muted">{{ $breadcrumb }}</span>
            @endisset
            @isset($breadcrumb_item)
                <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-400"></i>
                <span class="font-medium text-slate-800 dark:text-slate-100">{{ $breadcrumb_item }}</span>
            @endisset
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <button type="button" class="btn-icon" x-data="theme" @click="toggle()" aria-label="Toggle dark mode">
                <i data-lucide="moon" class="h-4 w-4 dark:hidden"></i>
                <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i>
            </button>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-slate-100 dark:hover:bg-slate-800"
                        @click="open = !open">
                    <x-avatar :user="$user" size="h-8 w-8" />
                    <span class="hidden text-sm font-medium text-slate-700 sm:block dark:text-slate-200">{{ $user->name }}</span>
                    <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400"></i>
                </button>
                <div x-cloak x-show="open" x-transition
                     class="card absolute right-0 mt-2 w-56 overflow-hidden p-1 shadow-lg">
                    <div class="px-3 py-2">
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $user->name }}</div>
                        <div class="muted text-xs">{{ $user->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i data-lucide="log-out" class="h-4 w-4"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    @if (session('login_as_original_user'))
        <div class="flex flex-wrap items-center gap-3 bg-amber-100 px-4 py-2 text-sm text-amber-900 sm:px-6 dark:bg-amber-500/10 dark:text-amber-300">
            <i data-lucide="user-cog" class="h-4 w-4"></i>
            You are viewing the app as <strong>{{ $user->name }}</strong>.
            <form method="POST" action="{{ route('users.logoutAs') }}" class="ml-auto">
                @csrf
                <button class="btn btn-sm btn-secondary">Back to my account</button>
            </form>
        </div>
    @endif

    <main class="mx-auto max-w-7xl p-4 sm:p-6">
        <x-flash />
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
