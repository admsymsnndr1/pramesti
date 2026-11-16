<?php

namespace Tests\Feature;

use App\Models\ProjectDailySnapshot;
use App\Services\ProjectDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProjectData;
use Tests\TestCase;

class ProjectDashboardServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProjectData;

    private ProjectDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->service = app(ProjectDashboardService::class);
    }

    public function test_metrics_count_leaf_tasks_per_status_and_progress_per_level(): void
    {
        $project = $this->makeProject();
        $phase = $this->makeItem($project, ['type' => 'wbs', 'weight' => 1]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 100, 'weight' => 1]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 50, 'weight' => 1]);
        $this->makeItem($project, ['parent_id' => $phase->id, 'progress' => 0, 'weight' => 2]);

        $metrics = $this->service->metrics($project->fresh());

        $this->assertSame(3, $metrics['total'], 'only leaf items are tasks');
        $this->assertSame(1, $metrics['counts']['complete']);
        $this->assertSame(1, $metrics['counts']['on_progress']);
        $this->assertSame(1, $metrics['counts']['open']);
        // (100 + 50 + 0 * 2) / 4
        $this->assertEquals(37.5, $metrics['progress']);
        $this->assertEquals([1 => 37.5, 2 => 37.5], $metrics['level_progress']);
    }

    public function test_planned_curve_spreads_each_task_over_its_dates_and_ends_at_100(): void
    {
        $project = $this->makeProject(null, ['start_date' => '2026-03-01', 'end_date' => '2026-03-10']);
        $this->makeItem($project, ['weight' => 1, 'start_date' => '2026-03-01', 'end_date' => '2026-03-05']);
        $this->makeItem($project, ['weight' => 1, 'start_date' => '2026-03-06', 'end_date' => '2026-03-10']);

        $curve = $this->service->plannedCurve($project, Carbon::parse('2026-03-01'), Carbon::parse('2026-03-10'));

        $this->assertEquals(10, $curve['2026-03-01']);  // 1/5 of the first half
        $this->assertEquals(50, $curve['2026-03-05']);  // first task fully planned
        $this->assertEquals(100, $curve['2026-03-10']);
        $this->assertSame(array_values($curve), collect($curve)->sort()->values()->all(), 'cumulative plan never goes down');
    }

    public function test_snapshot_command_stores_one_row_per_project_per_day(): void
    {
        $project = $this->makeProject();
        $this->makeItem($project, ['progress' => 100]);

        $this->artisan('dashboard:snapshot', ['--date' => '2026-05-04'])->assertSuccessful();
        $this->artisan('dashboard:snapshot', ['--date' => '2026-05-04'])->assertSuccessful();

        $snapshot = ProjectDailySnapshot::sole();
        $this->assertSame('2026-05-04', $snapshot->snapshot_date->toDateString());
        $this->assertSame(1, $snapshot->complete);
        $this->assertEquals(100, $snapshot->progress);
    }
}
