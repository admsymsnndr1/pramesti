@php $user ??= null; @endphp

<div class="card max-w-2xl">
    <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
        <div class="sm:col-span-2 flex items-center gap-4">
            <x-avatar :user="$user" size="h-14 w-14" />
            <div class="flex-1">
                <label class="label" for="photo">Photo <span class="muted font-normal">(optional, max 2 MB)</span></label>
                <input id="photo" type="file" name="photo" accept="image/*"
                       class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-200">
            </div>
        </div>

        <div>
            <label class="label" for="name">Name</label>
            <input id="name" type="text" name="name" class="input" value="{{ old('name', $user?->name) }}" minlength="2" required>
        </div>

        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" class="input" value="{{ old('email', $user?->email) }}" required>
        </div>

        <div>
            <label class="label" for="password">
                Password @if ($user)<span class="muted font-normal">(leave blank to keep)</span>@endif
            </label>
            <input id="password" type="password" name="password" class="input" minlength="6" autocomplete="new-password" @required(!$user)>
        </div>

        <div>
            <label class="label" for="gender">Gender</label>
            <select id="gender" name="gender" class="input" required>
                <option value="">— Select —</option>
                @foreach (['male' => 'Male', 'female' => 'Female'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $user?->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label" for="role">Role</label>
            <select id="role" name="role" class="input" required>
                <option value="">— Select role —</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role', $user?->role) === $role->name)>{{ $role->display_name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label" for="active">Status</label>
            <select id="active" name="active" class="input" required>
                <option value="1" @selected(old('active', $user?->active ?? true) == 1)>Active</option>
                <option value="0" @selected(old('active', $user?->active ?? true) == 0)>Inactive</option>
            </select>
        </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save user</button>
    </div>
</div>
