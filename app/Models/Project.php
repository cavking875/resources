<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'project_name', 'client_name', 'location', 'postcode',
        'latitude', 'longitude', 'region', 'project_value',
        'start_date', 'end_date', 'project_stage', 'project_type',
        'contract_type', 'delivery_model', 'complexity_level',
        'risk_level', 'planning_intensity', 'commercial_intensity',
        'site_presence_required', 'framework_name',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'project_value' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function forecasts(): HasMany
    {
        return $this->hasMany(ResourceForecast::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ResourceAllocation::class);
    }

    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class);
    }
}
