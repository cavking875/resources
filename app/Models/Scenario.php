<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends Model
{
    protected $table = 'scenarios';
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['scenario_name', 'created_by', 'base_case'];

    protected $casts = [
        'base_case' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ScenarioProject::class, 'scenario_id');
    }
}
