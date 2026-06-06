<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImport extends Model
{
    protected $table = 'project_imports';
    public $timestamps = false;
    const CREATED_AT = 'uploaded_at';

    protected $fillable = [
        'file_name', 'file_hash', 'uploaded_by', 'import_status',
        'rows_imported', 'rows_failed', 'error_report_path', 'mapping_json',
    ];

    protected $casts = [
        'mapping_json' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
