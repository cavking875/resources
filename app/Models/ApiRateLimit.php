<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRateLimit extends Model
{
    protected $table = 'api_rate_limits';
    public $timestamps = false;
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['rate_key', 'window_start', 'attempts'];

    protected $casts = [
        'window_start' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
