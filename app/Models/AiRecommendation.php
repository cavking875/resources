<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    protected $table = 'ai_recommendations';
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'project_id', 'recommendation_type', 'recommendation_text',
        'raw_json', 'confidence_score',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
