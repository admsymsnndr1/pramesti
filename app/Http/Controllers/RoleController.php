<?php

namespace App\Http\Controllers;

use App\Exports\RoleExport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelWriter;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles.manage');
    }

    /**
     * Display all roles
     */
    public function index()
    {
        $breadcrumb = 'Roles';
        $breadcrumb_item = 'List';
        $roles = Role::withCount(['users', 'permissions'])->orderBy('id')->get();
        $totalPermissions = Permission::count();

        return view('roles.index', compact('breadcrumb', 'breadcrumb_item', 'roles', 'totalPermissions'));
    }

    /* ======================
     * EXPORT
     * ====================== */
    public function export(ExcelWriter $excel)
    {
        return $excel->download(new RoleExport(), 'daftar-role-' . now()->format('Y-m-d') . '.xlsx');
    }

    /* ======================
     * CREATE
     * ====================== */
    public function create()
    {
        $breadcrumb = 'Roles';
        $breadcrumb_item = 'Create';
        $role = new Role();
        $permissionGroups = $this->permissionGroups();
        $selected = [];

        return view('roles.create', compact('breadcrumb', 'breadcrumb_item', 'role', 'permissionGroups', 'selected'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);

        DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'         => $data['name'],
                'display_name' => $data['display_name'],
                'description'  => $data['description'] ?? null,
            ]);
            $role->permissions()->sync($data['permissions'] ?? []);
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created');
    }

    /* ======================
     * EDIT
     * ====================== */
    public function edit(Role $role)
    {
        $breadcrumb = 'Roles';
        $breadcrumb_item = 'Edit';
        $permissionGroups = $this->permissionGroups();
        $selected = $role->isSuperRole()
            ? Permission::pluck('id')->all()
            : $role->permissions()->pluck('permissions.id')->all();

        return view('roles.edit', compact('breadcrumb', 'breadcrumb_item', 'role', 'permissionGroups', 'selected'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role);

        DB::transaction(function () use ($data, $role) {
            $oldName = $role->name;

            $role->update([
                // system role codes are used in the code and cannot change
                'name'         => $role->is_system ? $role->name : $data['name'],
                'display_name' => $data['display_name'],
                'description'  => $data['description'] ?? null,
            ]);

            // admin always has every permission
            if (!$role->isSuperRole()) {
                $role->permissions()->sync($data['permissions'] ?? []);
            }

            if ($oldName !== $role->name) {
                User::where('role_id', $role->id)->update(['role' => $role->name]);
            }
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated');
    }

    /* ======================
     * DELETE
     * ====================== */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'This role is still assigned to users; move them to another role first');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                Rule::requiredIf(!$role || !$role->is_system),
                'nullable',
                'alpha_dash',
                'max:50',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'display_name'  => 'required|string|max:100',
            'description'   => 'nullable|string|max:255',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ], [
            'name.alpha_dash' => 'The code may only contain letters, numbers, - and _ (no spaces).',
        ]);
    }

    private function permissionGroups()
    {
        return Permission::orderBy('id')->get()->groupBy('group');
    }
}
