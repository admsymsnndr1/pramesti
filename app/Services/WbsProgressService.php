<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WbsProgressService
{
    /**
     * Recalculate the progress of every parent item in a project, then the project itself.
     *
     * - Leaf items (no children) keep their own progress (entered manually).
     * - Parent progress = weighted average of its children. Weight is relative
     *   between siblings; if all siblings have weight 0, a simple average is used.
     *
     * The whole project is recalculated at once (one query), so it doesn't matter
     * which item changed, was moved or deleted.
     */
    public function recalculateProject(int $projectId): float
    {
        return DB::transaction(function () use ($projectId) {
            $items = DB::table('wbs_items')
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->get(['id', 'parent_id', 'progress', 'weight']);

            $childrenOf = $items->groupBy(fn ($item) => $item->parent_id ?? 0);
            $computed = [];
            $changed = [];

            $compute = function ($item, array $path = []) use (&$compute, &$computed, &$changed, $childrenOf) {
                if (isset($computed[$item->id])) {
                    return $computed[$item->id];
                }

                $children = $childrenOf->get($item->id);

                // leaf, or a broken parent loop: use the stored value
                if (!$children || isset($path[$item->id])) {
                    return $computed[$item->id] = (float) $item->progress;
                }

                $path[$item->id] = true;
                $progress = $this->weightedAverage($children->map(fn ($child) => (object) [
                    'progress' => $compute($child, $path),
                    'weight'   => (float) $child->weight,
                ]));

                if (abs($progress - (float) $item->progress) >= 0.01) {
                    $changed[$item->id] = $progress;
                }

                return $computed[$item->id] = $progress;
            };

            $roots = $childrenOf->get(0, collect());
            $projectProgress = $roots->isEmpty()
                ? 0.0
                : $this->weightedAverage($roots->map(fn ($root) => (object) [
                    'progress' => $compute($root),
                    'weight'   => (float) $root->weight,
                ]));

            $now = now();
            foreach ($changed as $id => $progress) {
                DB::table('wbs_items')->where('id', $id)->update([
                    'progress'            => $progress,
                    'progress_updated_at' => $now,
                    'updated_at'          => DB::raw('updated_at'), // rollup is not a user edit
                ]);
            }

            DB::table('projects')->where('id', $projectId)->update([
                'progress'   => $projectProgress,
                'updated_at' => DB::raw('updated_at'),
            ]);

            return $projectProgress;
        });
    }

    private function weightedAverage(Collection $items): float
    {
        $totalWeight = $items->sum('weight');

        if ($totalWeight <= 0) {
            return round((float) $items->avg('progress'), 2);
        }

        return round($items->sum(fn ($item) => $item->progress * $item->weight) / $totalWeight, 2);
    }
}
