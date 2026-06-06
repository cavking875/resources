<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSkill extends Model
{
    protected $table = 'staff_skills';
    public $timestamps = false;

    protected $fillable = ['staff_id', 'skill_name'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
