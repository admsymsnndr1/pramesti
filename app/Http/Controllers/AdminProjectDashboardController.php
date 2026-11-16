<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailySnapshot;
use App\Models\WbsItem;
use App\Services\ProjectDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminProjectDashboardController extends Controller
{
    /**
     * Days of history shown in the trend charts.
     */
    private const TREND_DAYS = 31;

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function __construct(private ProjectDashboardService $dashboard)
    {
        $this->middleware('permission:dashboard.admin_project');
    }

    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'ongoing');
        $today = ProjectDashboardService::today();
        $trendFrom = $today->copy()->subDays(self::TREND_DAYS - 1);

        $projects = Project::query()
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('name')
            ->get();

        $snapshots = ProjectDailySnapshot::whereIn('project_id', $projects->pluck('id'))
            ->orderBy('snapshot_date')
            ->get()
            ->groupBy('project_id');

        $firstSnapshot = ProjectDailySnapshot::min('snapshot_date');

        $data = $projects->map(function (Project $project) use ($snapshots, $today, $trendFrom) {
            $metrics = $this->dashboard->metrics($project);
            $deepestLevel = $metrics['level_progress'] ? max(array_keys($metrics['level_progress'])) : null;

            // history per day, today's point is always the live value
            $history = ($snapshots->get($project->id) ?? collect())
                ->keyBy(fn ($snapshot) => $snapshot->snapshot_date->toDateString())
                ->map(fn ($snapshot) => [
                    'complete' => $snapshot->complete,
                    'progress' => $snapshot->progress,
                    'levels'   => $snapshot->level_progress ?? [],
                ]);
            $history[$today->toDateString()] = [
                'complete' => $metrics['counts']['complete'],
                'progress' => $metrics['progress'],
                'levels'   => $metrics['level_progress'],
            ];
            $history = $history->sortKeys();

            $trend = $history->filter(fn ($row, $date) => $date >= $trendFrom->toDateString());

            return [
                'id'             => $project->id,
                'name'           => $project->name,
                'code'           => $project->code,
                'status'         => $project->status,
                'counts'         => $metrics['counts'],
                'total'          => $metrics['total'],
                'progress'       => $metrics['progress'],
                'level_progress' => $metrics['level_progress'],
                'deepest_level'  => $deepestLevel,
                'trend' => [
                    'labels'   => $trend->keys()->map(fn ($date) => $this->label($date))->values(),
                    'complete' => $trend->pluck('complete')->values(),
                    'wbs'      => $trend->map(fn ($row) => $row['levels'][$deepestLevel] ?? null)->values(),
                ],
                'scurve' => $this->scurve($project, $history, $today),
            ];
        });

        return view('dashboard.admin-project', [
            'projects'      => $data,
            'statuses'      => WbsItem::STATUSES,
            'statusFilter'  => $statusFilter,
            'firstSnapshot' => $firstSnapshot ? Carbon::parse($firstSnapshot) : null,
        ]);
    }

    /**
     * Planned vs actual cumulative progress, per day from the project start until
     * the project end, the last task end date, or today if the project runs late.
     */
    private function scurve(Project $project, $history, Carbon $today): ?array
    {
        if (!$project->start_date || !$project->end_date) {
            return null;
        }

        $tz = ProjectDashboardService::TIMEZONE;
        $from = Carbon::parse($project->start_date, $tz)->startOfDay();
        $to = Carbon::parse($project->end_date, $tz)->startOfDay();

        $lastTaskEnd = $project->wbsItems()->max('end_date');
        if ($lastTaskEnd && Carbon::parse($lastTaskEnd, $tz)->gt($to)) {
            $to = Carbon::parse($lastTaskEnd, $tz)->startOfDay();
        }
        if ($today->gt($to) && $project->status !== 'completed') {
            $to = $today->copy();
        }

        $plan = $this->dashboard->plannedCurve($project, $from, $to);

        return [
            'labels' => collect(array_keys($plan))->map(fn ($date) => $this->label($date, true))->values(),
            'plan'   => array_values($plan),
            'actual' => collect(array_keys($plan))
                ->map(fn ($date) => isset($history[$date]) ? $history[$date]['progress'] : null)
                ->values(),
            'today_plan' => $plan[$today->toDateString()] ?? null,
        ];
    }

    private function label(string $date, bool $withYear = false): string
    {
        $day = Carbon::parse($date);

        return $day->day . '-' . self::MONTHS[$day->month - 1] . ($withYear ? '-' . $day->format('y') : '');
    }
}
