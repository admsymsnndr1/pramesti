<?php

namespace App\Observers;

use App\Models\WbsItem;
use App\Services\WbsProgressService;

class WbsItemObserver
{
    /**
     * Fields that affect the progress rollup.
     */
    private const ROLLUP_FIELDS = ['progress', 'weight', 'parent_id', 'project_id'];

    public function __construct(private WbsProgressService $progress)
    {
    }

    public function saving(WbsItem $item)
    {
        if ($item->isDirty('progress')) {
            $item->progress_updated_at = now();

            if (!$item->isDirty('status')) {
                $item->status = WbsItem::statusForProgress((float) $item->progress, $item->status);
            }
        }
    }

    public function saved(WbsItem $item)
    {
        if (!$item->wasRecentlyCreated && !$item->wasChanged(self::ROLLUP_FIELDS)) {
            return;
        }

        $this->progress->recalculateProject($item->project_id);

        // item moved to another project: the old project also needs recalculating
        if ($item->wasChanged('project_id') && $item->getOriginal('project_id')) {
            $this->progress->recalculateProject($item->getOriginal('project_id'));
        }
    }

    public function deleted(WbsItem $item)
    {
        $this->progress->recalculateProject($item->project_id);
    }
}
