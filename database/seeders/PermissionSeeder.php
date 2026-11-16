<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Permission list per module: name => display_name
     */
    private array $permissions = [
        'projects' => [
            'projects.view'   => 'View projects',
            'projects.create' => 'Create projects',
            'projects.update' => 'Edit projects',
            'projects.delete' => 'Delete projects',
            'projects.report' => 'Export project report',
        ],
        'wbs' => [
            'wbs.view'   => 'View WBS',
            'wbs.create' => 'Create WBS items',
            'wbs.update' => 'Edit WBS items',
            'wbs.delete' => 'Delete WBS items',
        ],
        'tasks' => [
            'tasks.view'   => 'View my tasks (as PIC)',
            'tasks.assign' => 'Assign a PIC to tasks',
        ],
        'deliverables' => [
            'deliverables.view'    => 'View deliverables',
            'deliverables.create'  => 'Create deliverables',
            'deliverables.update'  => 'Edit deliverables',
            'deliverables.delete'  => 'Delete deliverables',
            'deliverables.upload'  => 'Upload deliverable files',
            'deliverables.approve' => 'Approve deliverables',
        ],
        'users' => [
            'users.view'     => 'View users',
            'users.create'   => 'Create users',
            'users.update'   => 'Edit users',
            'users.delete'   => 'Delete users',
            'users.login_as' => 'Log in as another user',
        ],
        'roles' => [
            'roles.manage' => 'Manage roles & permissions',
        ],
        'dashboard' => [
            'dashboard.admin_project' => 'View project analytics dashboard',
        ],
    ];

    /**
     * Default permissions per role (admin, pm, team_leader, developer, qa, admin_project).
     * '*' = all permissions.
     */
    private array $defaults = [
        'admin' => ['*'],
        'pm' => [
            'projects.view', 'projects.create', 'projects.update', 'projects.delete', 'projects.report',
            'wbs.view', 'wbs.create', 'wbs.update', 'wbs.delete',
            'tasks.assign',
            'deliverables.view', 'deliverables.create', 'deliverables.update', 'deliverables.delete',
            'deliverables.upload', 'deliverables.approve',
        ],
        // like PM without delete rights, plus their own tasks
        'team_leader' => [
            'projects.view', 'projects.create', 'projects.update', 'projects.report',
            'wbs.view', 'wbs.create', 'wbs.update',
            'tasks.view', 'tasks.assign',
            'deliverables.view', 'deliverables.create', 'deliverables.update',
            'deliverables.upload', 'deliverables.approve',
        ],
        'developer' => [
            'tasks.view',
            'deliverables.view', 'deliverables.create', 'deliverables.update', 'deliverables.delete',
            'deliverables.upload',
        ],
        // like Developer, plus viewing projects/WBS and approving deliverables
        'qa' => [
            'projects.view',
            'wbs.view',
            'tasks.view',
            'deliverables.view', 'deliverables.create', 'deliverables.update', 'deliverables.delete',
            'deliverables.upload', 'deliverables.approve',
        ],
        // read-only: WBS and the project analytics dashboard
        'admin_project' => [
            'wbs.view',
            'dashboard.admin_project',
        ],
    ];

    /**
     * Safe to run repeatedly: permissions are upserted, and default permissions
     * are only given to roles that have no permissions yet (changes made through
     * the UI are not overwritten).
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        foreach ($this->permissions as $group => $items) {
            foreach ($items as $name => $displayName) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $name],
                    ['display_name' => $displayName, 'group' => $group, 'updated_at' => $now]
                );
            }
        }
        DB::table('permissions')->whereNull('created_at')->update(['created_at' => $now]);

        $permissionIds = DB::table('permissions')->pluck('id', 'name');

        foreach ($this->defaults as $roleName => $names) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if (!$role || DB::table('permission_role')->where('role_id', $role->id)->exists()) {
                continue;
            }

            $ids = $names === ['*']
                ? $permissionIds->values()
                : $permissionIds->only($names)->values();

            DB::table('permission_role')->insert(
                $ids->map(fn ($id) => ['role_id' => $role->id, 'permission_id' => $id])->all()
            );
        }
    }
}
