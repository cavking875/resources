<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplexityMultiplier extends Model
{
    protected $table = 'complexity_multipliers';
    public $timestamps = false;

    protected $fillable = ['complexity_level', 'multiplier'];
}
