<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDailySnapshot;
use App\Models\WbsItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the admin project dashboard, computed from the WBS of a project.
 *
 * - Tasklist = leaf WBS items (items without children), counted per wbs_items.status.
 * - Share of an item in the project = product of its weight relative to its siblings
 *   along the path (same weighting as WbsProgressService).
 * - Progress of WBS level L = average progress of the items at level L weighted by
 *   their share (level 1 = project progress).
 * - S-curve plan = leaf shares spread over their start/end dates.
 */
class ProjectDashboardService
{
    /**
     * Snapshot dates follow local working days, the app itself runs in UTC.
     */
    public const TIMEZONE = 'Asia/Jakarta';

    public static function today(): Carbon
    {
        return now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Current numbers of a project: status counts, progress and progress per WBS level.
     */
    public function metrics(Project $project): array
    {
        $tree = $this->tree($project->id);

        $counts = array_fill_keys(array_keys(WbsItem::STATUSES), 0);
        foreach ($tree['leaves'] as $leaf) {
            $counts[$leaf->status] = ($counts[$leaf->status] ?? 0) + 1;
        }

        $levelProgress = [];
        foreach ($tree['items']->groupBy('level')->sortKeys() as $level => $items) {
            $totalShare = $items->sum('share');
            $levelProgress[$level] = $totalShare > 0
                ? round($items->sum(fn ($item) => $item->progress * $item->share) / $totalShare, 2)
                : round((float) $items->avg('progress'), 2);
        }

        return [
            'counts'         => $counts,
            'total'          => count($tree['leaves']),
            'progress'       => (float) DB::table('projects')->where('id', $project->id)->value('progress'),
            'level_progress' => $levelProgress,
        ];
    }

    /**
     * Save (or overwrite) today's numbers of every project.
     */
    public function snapshotAll(?Carbon $date = null): int
    {
        $date = ($date ?? self::today())->toDateString();
        $projects = Project::all();

        foreach ($projects as $project) {
            $metrics = $this->metrics($project);

            ProjectDailySnapshot::updateOrCreate(
                ['project_id' => $project->id, 'snapshot_date' => $date],
                $metrics['counts'] + [
                    'total'          => $metrics['total'],
                    'progress'       => $metrics['progress'],
                    'level_progress' => $metrics['level_progress'],
                ]
            );
        }

        return $projects->count();
    }

    /**
     * Planned cumulative progress per day (S-curve): each leaf's share is spread
     * evenly over its start_date..end_date. Leaves without dates are left out and
     * the remaining shares are scaled so the plan ends at 100%.
     *
     * @return array<string, float>  date (Y-m-d) => planned %
     */
    public function plannedCurve(Project $project, Carbon $from, Carbon $to): array
    {
        $leaves = collect($this->tree($project->id)['leaves'])
            ->filter(fn ($leaf) => $leaf->start_date && $leaf->end_date && $leaf->share > 0)
            ->map(function ($leaf) {
                $start = Carbon::parse($leaf->start_date, self::TIMEZONE)->startOfDay();
                $end = Carbon::parse($leaf->end_date, self::TIMEZONE)->startOfDay();
                if ($end->lt($start)) {
                    [$start, $end] = [$end, $start];
                }

                return (object) [
                    'start' => $start,
                    'days'  => $start->diffInDays($end) + 1,
                    'share' => $leaf->share,
                ];
            });

        $totalShare = $leaves->sum('share');
        $curve = [];

        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            // compare whole local days, whatever timezone $from / $to were given in
            $day = Carbon::parse($date->toDateString(), self::TIMEZONE)->startOfDay();
            $planned = 0.0;
            foreach ($leaves as $leaf) {
                $elapsed = $leaf->start->diffInDays($day, false) + 1;
                $planned += $leaf->share * max(0, min(1, $elapsed / $leaf->days));
            }
            $curve[$day->toDateString()] = $totalShare > 0 ? round($planned / $totalShare * 100, 2) : 0.0;
        }

        return $curve;
    }

    /**
     * WBS items of a project with their level (root = 1), the leaves and each leaf's share.
     */
    private function tree(int $projectId): array
    {
        $items = DB::table('wbs_items')
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->get(['id', 'parent_id', 'status', 'progress', 'weight', 'start_date', 'end_date']);

        $childrenOf = $items->groupBy(fn ($item) => $item->parent_id ?? 0);
        $leaves = [];

        // breadth-first from the roots; items whose parent is missing are not reachable and are skipped
        $level = 1;
        $current = collect([[$childrenOf->get(0, collect()), 1.0]]);
        $seen = [];

        while ($current->isNotEmpty()) {
            $next = collect();

            foreach ($current as [$siblings, $parentShare]) {
                $totalWeight = $siblings->sum(fn ($item) => (float) $item->weight);

                foreach ($siblings as $item) {
                    if (isset($seen[$item->id])) {
                        continue;
                    }
                    $seen[$item->id] = true;

                    $item->level = $level;
                    $item->share = $parentShare * ($totalWeight > 0
                        ? (float) $item->weight / $totalWeight
                        : 1 / $siblings->count());

                    $children = $childrenOf->get($item->id);
                    if ($children) {
                        $next->push([$children, $item->share]);
                    } else {
                        $leaves[] = $item;
                    }
                }
            }

            $current = $next;
            $level++;
        }

        return [
            'items'  => $items->filter(fn ($item) => isset($seen[$item->id]))->values(),
            'leaves' => $leaves,
        ];
    }
}
