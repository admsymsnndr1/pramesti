<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'pm_id',
        'created_by',
        'updated_by',
    ];

    /**
     * progress is filled automatically from the WBS rollup (WbsProgressService)
     */
    protected $casts = [
        'progress' => 'float',
    ];

    /* =====================
     | Relationships
     ===================== */

    public function pic()
    {
        return $this->belongsTo(User::class, 'pm_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function wbsItems()
    {
        return $this->hasMany(WbsItem::class, 'project_id');
    }
}
