<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('/api', function () {
    return response()->json([
        'service' => 'resource-planner-ai',
        'status' => 'ok',
        'health' => '/api/health',
    ]);
});

Route::get('/api/', function () {
    return response()->json([
        'service' => 'resource-planner-ai',
        'status' => 'ok',
        'health' => '/api/health',
    ]);
});
