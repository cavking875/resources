<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceForecast extends Model
{
    protected $table = 'resource_forecasts';

    protected $fillable = [
        'project_id', 'role_id', 'month', 'required_fte', 'source', 'ai_reason',
    ];

    protected $casts = [
        'month' => 'date',
        'required_fte' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function resourceRole(): BelongsTo
    {
        return $this->belongsTo(ResourceRole::class, 'role_id');
    }
}
