<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'wbs_item_id',
        'name',
        'description',
        'url',
        'status',
        'uploaded_by',
    ];

    /* =====================
     | RELATIONS
     ===================== */

    public function task()
    {
        return $this->belongsTo(WbsItem::class, 'wbs_item_id');
    }

    public function files()
    {
        return $this->hasMany(DeliverableFile::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function wbsItem()
    {
        return $this->belongsTo(WbsItem::class);
    }

    public function getFirstFileAttribute()
    {
        return $this->files->first();
    }
}
