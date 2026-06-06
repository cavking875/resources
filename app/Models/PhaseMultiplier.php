<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseMultiplier extends Model
{
    protected $table = 'phase_multipliers';
    public $timestamps = false;

    protected $fillable = ['role_id', 'phase_name', 'multiplier'];

    public function resourceRole(): BelongsTo
    {
        return $this->belongsTo(ResourceRole::class, 'role_id');
    }
}
