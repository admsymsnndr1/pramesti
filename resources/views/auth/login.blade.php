<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- INTRO --}}
    <div class="relative hidden flex-col justify-between overflow-hidden bg-slate-900 p-12 text-white lg:flex">
        <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl"></div>

        <div class="relative flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600">
                <i data-lucide="kanban-square" class="h-5 w-5"></i>
            </span>
            <span class="text-lg font-semibold">{{ config('app.name') }}</span>
        </div>

        <div class="relative max-w-md">
            <h1 class="text-3xl font-semibold leading-tight">Plan the work, track every task, see the whole project.</h1>
            <p class="mt-4 text-slate-300">
                Work breakdown structure with automatic weighted progress, task assignment,
                deliverable review and an S-curve that compares plan against reality.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-slate-300">
                @foreach (['git-branch' => 'Multi-level WBS with weighted progress roll-up',
                           'activity' => 'Daily snapshots, trend charts and S-curve',
                           'shield-check' => 'Role-based access managed from the UI'] as $icon => $text)
                    <li class="flex items-center gap-3">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4 text-brand-300"></i>{{ $text }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative text-xs text-slate-500">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
    </div>

    {{-- FORM --}}
    <div class="flex items-center justify-center p-6">
        <div class="w-full max-w-sm" x-data="login">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <i data-lucide="kanban-square" class="h-5 w-5"></i>
                </span>
                <span class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ config('app.name') }}</span>
            </div>

            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">Sign in</h2>
            <p class="muted mt-1 text-sm">Use your account to continue.</p>

            <form id="login-form" class="mt-8 space-y-5" @submit.prevent="submit" novalidate>
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" autocomplete="username" required autofocus
                           x-model="email" class="input" :class="errors.email && 'input-error'">
                    <p class="error" x-show="errors.email" x-text="errors.email"></p>
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                               x-model="password" class="input pr-10" :class="errors.password && 'input-error'">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                :aria-label="show ? 'Hide password' : 'Show password'">
                            <i data-lucide="eye" class="h-4 w-4" x-show="!show"></i>
                            <i data-lucide="eye-off" class="h-4 w-4" x-show="show" x-cloak></i>
                        </button>
                    </div>
                    <p class="error" x-show="errors.password" x-text="errors.password"></p>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="checkbox" x-model="remember"> Remember me
                </label>

                <p x-show="message" x-text="message" x-cloak
                   class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"></p>

                <button type="submit" class="btn btn-primary w-full" :disabled="loading">
                    <span x-show="!loading">Sign in</span>
                    <span x-show="loading" x-cloak>Signing in…</span>
                </button>
            </form>

            @if (app()->environment('local'))
                <div class="mt-8 rounded-lg border border-dashed border-slate-300 p-4 text-xs dark:border-slate-700">
                    <div class="font-medium text-slate-700 dark:text-slate-200">Demo accounts (password: <code>password</code>)</div>
                    <div class="mt-2 grid grid-cols-2 gap-1 muted">
                        @foreach (['admin@example.com' => 'Admin', 'pm@example.com' => 'Project Manager', 'dev@example.com' => 'Developer', 'qa@example.com' => 'QA', 'analytics@example.com' => 'Admin Project'] as $demoEmail => $demoRole)
                            <button type="button" class="text-left hover:text-brand-600"
                                    @click="useDemo('{{ $demoEmail }}')">{{ $demoRole }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('login', () => ({
            email: '',
            password: '',
            remember: false,
            show: false,
            loading: false,
            errors: {},
            message: '',

            useDemo(email) {
                this.email = email;
                this.password = 'password';
                this.errors = {};
            },

            async submit() {
                this.loading = true;
                this.errors = {};
                this.message = '';

                try {
                    const response = await fetch(@json(route('login.check')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ email: this.email, password: this.password, remember: this.remember }),
                    });
                    const data = await response.json();

                    if (response.ok) {
                        window.location.href = data.redirect;
                        return;
                    }

                    if (data.errors) {
                        Object.entries(data.errors).forEach(([key, messages]) => this.errors[key] = messages[0]);
                    } else {
                        this.message = data.message || 'Login failed';
                    }
                } catch (error) {
                    this.message = 'Cannot reach the server, please try again.';
                }

                this.loading = false;
            },
        }));
    });
</script>
</body>
</html>
