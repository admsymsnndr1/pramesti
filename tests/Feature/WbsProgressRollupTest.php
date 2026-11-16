<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProjectData;
use Tests\TestCase;

class WbsProgressRollupTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProjectData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_parent_progress_is_the_weighted_average_of_its_children(): void
    {
        $project = $this->makeProject();
        $phase = $this->makeItem($project, ['type' => 'wbs', 'weight' => 1]);

        $this->makeItem($project, ['parent_id' => $phase->id, 'weight' => 3, 'progress' => 100]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'weight' => 1, 'progress' => 20]);

        // (100 * 3 + 20 * 1) / 4 = 80
        $this->assertEquals(80, $phase->fresh()->progress);
        $this->assertEquals(80, $project->fresh()->progress);
    }

    public function test_siblings_without_weight_fall_back_to_a_simple_average(): void
    {
        $project = $this->makeProject();
        $phase = $this->makeItem($project, ['type' => 'wbs']);

        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 100]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 0]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 50]);

        $this->assertEquals(50, $phase->fresh()->progress);
    }

    public function test_progress_rolls_up_through_several_levels(): void
    {
        $project = $this->makeProject();
        $design = $this->makeItem($project, ['type' => 'wbs', 'weight' => 20]);
        $build = $this->makeItem($project, ['type' => 'wbs', 'weight' => 80]);
        $backend = $this->makeItem($project, ['type' => 'wbs', 'parent_id' => $build->id, 'weight' => 1]);

        $this->makeItem($project, ['parent_id' => $design->id, 'progress' => 100, 'weight' => 1]);
        $leaf = $this->makeItem($project, ['parent_id' => $backend->id, 'progress' => 0, 'weight' => 1]);

        $this->assertEquals(20, $project->fresh()->progress);

        $leaf->update(['progress' => 50]);

        $this->assertEquals(50, $backend->fresh()->progress);
        $this->assertEquals(50, $build->fresh()->progress);
        // 100 * 0.2 + 50 * 0.8
        $this->assertEquals(60, $project->fresh()->progress);
    }

    public function test_deleting_and_moving_items_recalculates_the_parents(): void
    {
        $project = $this->makeProject();
        $a = $this->makeItem($project, ['type' => 'wbs', 'weight' => 1]);
        $b = $this->makeItem($project, ['type' => 'wbs', 'weight' => 1]);

        $done = $this->makeItem($project, ['parent_id' => $a->id, 'progress' => 100, 'weight' => 1]);
        $this->makeItem($project, ['parent_id' => $a->id, 'progress' => 0, 'weight' => 1]);
        $this->makeItem($project, ['parent_id' => $b->id, 'progress' => 0, 'weight' => 1]);

        $this->assertEquals(50, $a->fresh()->progress);

        $done->update(['parent_id' => $b->id]);
        $this->assertEquals(0, $a->fresh()->progress);
        $this->assertEquals(50, $b->fresh()->progress);

        $done->delete();
        $this->assertEquals(0, $b->fresh()->progress);
        $this->assertEquals(0, $project->fresh()->progress);
    }

    public function test_status_follows_progress_unless_it_was_set_explicitly(): void
    {
        $project = $this->makeProject();
        $task = $this->makeItem($project);

        $task->update(['progress' => 40]);
        $this->assertSame('on_progress', $task->fresh()->status);

        $task->update(['status' => 'on_testing']);
        $task->update(['progress' => 90]);
        $this->assertSame('on_testing', $task->fresh()->status, 'testing is kept until the task is done');

        $task->update(['progress' => 100]);
        $this->assertSame('complete', $task->fresh()->status);
    }

    public function test_parent_progress_cannot_be_overwritten_from_the_edit_form(): void
    {
        $admin = $this->makeUser('admin');
        $project = $this->makeProject($admin);
        $phase = $this->makeItem($project, ['type' => 'wbs', 'weight' => 1]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 30, 'weight' => 1]);

        $this->actingAs($admin)
            ->put(route('wbs.update', $phase->id), ['name' => 'Phase', 'progress' => 99, 'weight' => 1])
            ->assertRedirect(route('projects.wbs', $project));

        $this->assertEquals(30, $phase->fresh()->progress);
    }

    public function test_new_items_get_outline_codes_per_project(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeProject($admin);
        $this->makeItem($other, ['type' => 'wbs', 'code' => '1']);
        $this->makeItem($other, ['type' => 'wbs', 'code' => '2']);

        $project = $this->makeProject($admin);
        $store = fn (array $data) => $this->actingAs($admin)->postJson(route('wbs.store'), $data + [
            'project_id' => $project->id, 'type' => 'task', 'weight' => 1,
            'start_date' => '2026-02-01', 'end_date' => '2026-02-10',
        ])->assertOk();

        $store(['name' => 'Phase']);
        $phase = $project->wbsItems()->firstOrFail();
        $store(['name' => 'Task A', 'parent_id' => $phase->id]);
        $store(['name' => 'Task B', 'parent_id' => $phase->id]);

        $this->assertSame(['1', '1.1', '1.2'], $project->wbsItems()->orderBy('id')->pluck('code')->all());
    }

    public function test_a_parent_from_another_project_is_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $foreign = $this->makeItem($this->makeProject($admin), ['type' => 'wbs']);
        $project = $this->makeProject($admin);

        $this->actingAs($admin)->postJson(route('wbs.store'), [
            'project_id' => $project->id, 'parent_id' => $foreign->id, 'name' => 'X', 'type' => 'task',
            'weight' => 1, 'start_date' => '2026-02-01', 'end_date' => '2026-02-10',
        ])->assertJsonValidationErrors('parent_id');
    }

    public function test_an_item_cannot_be_moved_under_its_own_child(): void
    {
        $admin = $this->makeUser('admin');
        $project = $this->makeProject($admin);
        $parent = $this->makeItem($project, ['type' => 'wbs']);
        $child = $this->makeItem($project, ['parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->put(route('wbs.update', $parent->id), ['name' => 'Parent', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');
    }
}
