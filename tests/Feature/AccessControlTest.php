<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProjectData;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProjectData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('projects.index'))->assertRedirect('login');
    }

    public function test_active_users_can_log_in_and_inactive_users_cannot(): void
    {
        $this->makeUser('developer', ['email' => 'active@example.com']);
        $this->makeUser('developer', ['email' => 'inactive@example.com', 'active' => false]);

        $this->postJson(route('login.check'), ['email' => 'inactive@example.com', 'password' => 'password'])
            ->assertStatus(401);
        $this->assertGuest();

        $this->postJson(route('login.check'), ['email' => 'active@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => route('dashboard')]);
        $this->assertAuthenticated();
    }

    public function test_each_role_only_reaches_the_pages_its_permissions_allow(): void
    {
        $project = $this->makeProject();

        $this->actingAs($this->makeUser('developer'));
        $this->get(route('projects.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('employee.tasks.index'))->assertOk();

        $this->actingAs($this->makeUser('pm'));
        $this->get(route('projects.index'))->assertOk();
        $this->get(route('projects.wbs', $project))->assertOk();
        $this->get(route('roles.index'))->assertForbidden();

        $this->actingAs($this->makeUser('admin_project'));
        $this->get(route('dashboard'))->assertRedirect(route('dashboard.admin-project'));
        $this->get(route('dashboard.admin-project'))->assertOk();
        $this->get(route('projects.create'))->assertForbidden();
    }

    public function test_permissions_changed_in_the_ui_take_effect(): void
    {
        $qa = $this->makeUser('qa');
        $this->actingAs($qa)->get(route('users.index'))->assertForbidden();

        Role::where('name', 'qa')->first()->permissions()
            ->attach(Permission::where('name', 'users.view')->value('id'));

        $this->actingAs($qa->fresh())->get(route('users.index'))->assertOk();
    }

    public function test_a_developer_cannot_open_someone_elses_task(): void
    {
        $owner = $this->makeUser('developer');
        $other = $this->makeUser('developer');
        $task = $this->makeItem($this->makeProject(), ['pic_id' => $owner->id]);

        $this->actingAs($owner)->get(route('employee.tasks.detail-task', $task))->assertOk();
        $this->actingAs($other)->get(route('employee.tasks.detail-task', $task))->assertForbidden();
    }

    public function test_task_descriptions_are_escaped(): void
    {
        $owner = $this->makeUser('developer');
        $task = $this->makeItem($this->makeProject(), [
            'pic_id'      => $owner->id,
            'description' => '<script>alert(1)</script>',
        ]);

        $this->actingAs($owner)
            ->get(route('employee.tasks.detail-task', $task))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
