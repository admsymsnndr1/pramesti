<?php

namespace Tests\Concerns;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\WbsItem;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

trait CreatesProjectData
{
    protected function seedRoles(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    protected function makeUser(string $roleName = 'admin', array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'role_id' => Role::where('name', $roleName)->value('id'),
        ])->fresh();
    }

    protected function makeProject(?User $owner = null, array $attributes = []): Project
    {
        $owner ??= $this->makeUser();

        return Project::create($attributes + [
            'name'       => 'Test project',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'status'     => 'ongoing',
            'pm_id'      => $owner->id,
            'created_by' => $owner->id,
        ]);
    }

    protected function makeItem(Project $project, array $attributes = []): WbsItem
    {
        return WbsItem::create(array_merge([
            'project_id' => $project->id,
            'name'       => 'Item',
            'type'       => 'task',
            'progress'   => 0,
            'weight'     => 0,
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'created_by' => $project->created_by,
        ], $attributes))->fresh();
    }
}
