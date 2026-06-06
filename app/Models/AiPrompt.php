<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    protected $table = 'ai_prompts';

    protected $fillable = [
        'name', 'prompt_type', 'system_prompt',
        'user_prompt_template', 'guardrails_json', 'active',
    ];

    protected $casts = [
        'guardrails_json' => 'array',
        'active' => 'boolean',
    ];
}
