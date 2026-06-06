<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'resource-planner-ai',
        'status' => 'ok',
        'docs_hint' => 'Use /api/health and /api/* endpoints.',
    ]);
});
