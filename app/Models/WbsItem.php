<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WbsItem extends Model
{
    use HasFactory;

    protected $table = 'wbs_items';

    /**
     * Tasklist status (leaf items): value => label
     */
    public const STATUSES = [
        'open'          => 'Open',
        'on_progress'   => 'On Progress',
        'complete'      => 'Complete',
        'on_testing'    => 'On Testing',
        'revisi_tester' => 'Revisi tester',
    ];

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'project_id',
        'parent_id',
        'pic_id',
        'description',
        'code',
        'name',
        'type',
        'status',
        'progress',
        'weight',
        'start_date',
        'end_date',
        'sort_order',
        'is_locked',
        'created_by',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'progress' => 'float',
        'progress_updated_at' => 'datetime',
        'weight' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_locked' => 'boolean',
    ];

    /**
     * Parent relationship (recursive)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /**
     * Children relationship (recursive)
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /**
     * Scope: filter by project
     */
    public function scopeDeliverables($query)
    {
        return $query
            ->where('type', 'task')
            ->whereDoesntHave('children');
    }

    /**
     * Parent items get their progress from the rollup (WbsProgressService, run by
     * WbsItemObserver); only leaf items can have their progress filled in manually.
     */
    public function isLeaf(): bool
    {
        return !$this->children()->exists();
    }

    /**
     * Status that follows a progress change, when the status itself was not changed:
     * 100% = complete, 0% = open, in between = on_progress.
     * On Testing / Revisi tester are kept until the item reaches 100%.
     */
    public static function statusForProgress(float $progress, ?string $current): string
    {
        if ($progress >= 100) {
            return 'complete';
        }

        if (in_array($current, ['on_testing', 'revisi_tester'], true)) {
            return $current;
        }

        return $progress > 0 ? 'on_progress' : 'open';
    }

    /**
     * IDs of all descendants (to stop an item from being moved under its own child).
     */
    public function descendantIds(): array
    {
        $ids = [];
        $level = [$this->id];

        while ($level) {
            $level = self::whereIn('parent_id', $level)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class, 'wbs_item_id');
    }
}
