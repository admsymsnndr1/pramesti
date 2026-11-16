<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Built-in roles. Their code (name) is referenced by the application, so they
     * are marked as system roles: they can be renamed but not deleted from the UI.
     */
    private array $roles = [
        'admin'         => ['Administrator', 'Full access to every module.'],
        'pm'            => ['Project Manager', 'Plans projects, builds the WBS and assigns tasks.'],
        'team_leader'   => ['Team Leader', 'Like a PM without delete rights, and also works on tasks.'],
        'developer'     => ['Developer', 'Works on assigned tasks and uploads deliverables.'],
        'qa'            => ['QA', 'Tests tasks and approves deliverables.'],
        'admin_project' => ['Admin Project', 'Read-only access to the WBS and the project analytics dashboard.'],
    ];

    public function run()
    {
        $now = now();

        foreach ($this->roles as $name => [$displayName, $description]) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'description'  => $description,
                    'is_system'    => true,
                    'updated_at'   => $now,
                ]
            );
        }

        DB::table('roles')->whereNull('created_at')->update(['created_at' => $now]);
    }
}
