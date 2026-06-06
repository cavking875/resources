<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceAllocation extends Model
{
    protected $table = 'resource_allocations';

    protected $fillable = [
        'project_id', 'staff_id', 'role_id', 'allocation_fte',
        'start_date', 'end_date', 'status', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'allocation_fte' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function resourceRole(): BelongsTo
    {
        return $this->belongsTo(ResourceRole::class, 'role_id');
    }
}
