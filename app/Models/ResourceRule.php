<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceRule extends Model
{
    protected $table = 'resource_rules';
    public $timestamps = false;

    protected $fillable = [
        'role_id', 'min_project_value', 'max_project_value',
        'base_fte', 'project_type', 'delivery_model', 'active',
    ];

    public function resourceRole(): BelongsTo
    {
        return $this->belongsTo(ResourceRole::class, 'role_id');
    }
}
