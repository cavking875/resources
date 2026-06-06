<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'staff';
    public $timestamps = false;

    protected $fillable = [
        'first_name', 'last_name', 'role_id', 'location', 'postcode',
        'latitude', 'longitude', 'region', 'employment_type',
        'salary_cost', 'day_rate', 'charge_out_rate', 'availability_status',
        'max_fte', 'current_fte', 'travel_radius_miles', 'preferred_projects',
        'certifications', 'start_date', 'end_date', 'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    public function resourceRole(): BelongsTo
    {
        return $this->belongsTo(ResourceRole::class, 'role_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(StaffSkill::class, 'staff_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ResourceAllocation::class, 'staff_id');
    }
}
