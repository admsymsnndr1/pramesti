<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDailySnapshot extends Model
{
    /**
     * Filled by the dashboard:snapshot command (ProjectDashboardService::snapshotAll)
     */
    protected $fillable = [
        'project_id',
        'snapshot_date',
        'open',
        'on_progress',
        'on_testing',
        'revisi_tester',
        'complete',
        'total',
        'progress',
        'level_progress',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'progress' => 'float',
        'level_progress' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
