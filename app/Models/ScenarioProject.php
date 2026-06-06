<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioProject extends Model
{
    protected $table = 'scenario_projects';
    public $timestamps = false;

    protected $fillable = [
        'scenario_id', 'project_id', 'adjusted_value',
        'adjusted_start_date', 'adjusted_end_date', 'included',
    ];

    protected $casts = [
        'adjusted_start_date' => 'date',
        'adjusted_end_date' => 'date',
        'included' => 'boolean',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
