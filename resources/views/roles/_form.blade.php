@php
    $groupLabels = [
        'dashboard'    => 'Dashboard',
        'projects'     => 'Projects',
        'wbs'          => 'WBS',
        'tasks'        => 'Tasks',
        'deliverables' => 'Deliverables',
        'users'        => 'Users',
        'roles'        => 'Roles',
    ];
    $locked = $role->exists && $role->isSuperRole();
    $checked = array_map('intval', old('permissions', $selected));
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- ROLE --}}
    <div class="card h-fit p-6">
        <div class="space-y-5">
            <div>
                <label class="label" for="display_name">Role name</label>
                <input id="display_name" type="text" name="display_name" class="input @error('display_name') input-error @enderror"
                       value="{{ old('display_name', $role->display_name) }}" placeholder="e.g. Quality Assurance" required>
                @error('display_name')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="name">Code</label>
                @if ($role->is_system)
                    <input id="name" type="text" class="input" value="{{ $role->name }}" disabled>
                    <p class="help">System role codes are used by the application and cannot change.</p>
                @else
                    <input id="name" type="text" name="name" class="input @error('name') input-error @enderror"
                           value="{{ old('name', $role->name) }}" placeholder="e.g. qa" required>
                    <p class="help">Lowercase, no spaces; - and _ are allowed.</p>
                @endif
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="description">Description</label>
                <textarea id="description" name="description" rows="3" class="input">{{ old('description', $role->description) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save role</button>
        </div>
    </div>

    {{-- PERMISSIONS --}}
    <div class="card lg:col-span-2" x-data="permissions">
        <div class="card-header">
            <h2 class="card-title mr-auto">Permissions</h2>
            @unless ($locked)
                <button type="button" class="btn btn-sm btn-secondary" @click="setAll(true)">Select all</button>
                <button type="button" class="btn btn-sm btn-secondary" @click="setAll(false)">Clear</button>
            @endunless
        </div>

        <div class="p-6">
            @if ($locked)
                <div class="mb-5 flex items-center gap-2 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:bg-brand-500/10 dark:text-brand-300">
                    <i data-lucide="info" class="h-4 w-4"></i> The Administrator role always has every permission.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($permissionGroups as $group => $permissions)
                    <fieldset class="rounded-lg border border-slate-200 p-4 dark:border-slate-800" data-group>
                        <label class="flex cursor-pointer items-center gap-2 font-medium text-slate-800 dark:text-slate-100">
                            <input type="checkbox" class="checkbox" data-group-toggle @change="toggleGroup($event)" @disabled($locked)>
                            {{ $groupLabels[$group] ?? ucfirst($group) }}
                        </label>
                        <div class="mt-3 space-y-2">
                            @foreach ($permissions as $permission)
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="checkbox" class="checkbox" data-permission @change="sync()"
                                           name="permissions[]" value="{{ $permission->id }}"
                                           @checked(in_array($permission->id, $checked, true)) @disabled($locked)>
                                    <span>{{ $permission->display_name }}</span>
                                    <code class="muted ml-auto text-[11px]">{{ $permission->name }}</code>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permissions', () => ({
            init() { this.$nextTick(() => this.sync()); },
            groups() { return this.$root.querySelectorAll('[data-group]'); },
            sync() {
                this.groups().forEach((group) => {
                    const items = group.querySelectorAll('[data-permission]');
                    const on = group.querySelectorAll('[data-permission]:checked').length;
                    const toggle = group.querySelector('[data-group-toggle]');
                    toggle.checked = on === items.length;
                    toggle.indeterminate = on > 0 && on < items.length;
                });
            },
            toggleGroup(event) {
                event.target.closest('[data-group]').querySelectorAll('[data-permission]').forEach((el) => el.checked = event.target.checked);
                this.sync();
            },
            setAll(value) {
                this.$root.querySelectorAll('[data-permission]').forEach((el) => el.checked = value);
                this.sync();
            },
        }));
    });
</script>
@endpush
