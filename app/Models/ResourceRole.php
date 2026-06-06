<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceRole extends Model
{
    protected $table = 'resource_roles';
    public $timestamps = false;

    protected $fillable = [
        'role_name', 'department', 'default_cost_rate', 'default_charge_rate', 'active',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(ResourceRule::class, 'role_id');
    }

    public function phaseMultipliers(): HasMany
    {
        return $this->hasMany(PhaseMultiplier::class, 'role_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'role_id');
    }
}
