<?php

namespace App\Models;

use App\Models\Deliverable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverableFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliverable_id',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'uploaded_by',
    ];

    /* =====================
     | RELATIONS
     ===================== */

    public function deliverable()
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* =====================
     | ACCESSOR
     ===================== */

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
