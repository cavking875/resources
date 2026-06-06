<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForecastController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ImportsController;
use App\Http\Controllers\Api\InsightsController;
use App\Http\Controllers\Api\ProjectsController;
use App\Http\Controllers\Api\ScenarioMapsController;
use App\Http\Controllers\Api\SettingsAuditController;
use App\Http\Controllers\Api\StaffAllocationsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'resource-planner-ai',
        'status' => 'ok',
        'health' => '/api/health',
    ]);
});

Route::get('/health', [HealthController::class, 'show']);

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('/forecast', [ForecastController::class, 'forecast']);
Route::post('/forecast/persist', [ForecastController::class, 'persist']);

Route::prefix('imports/projects')->group(function (): void {
    Route::post('/validate', [ImportsController::class, 'validateRows']);
    Route::post('/', [ImportsController::class, 'import']);
    Route::get('/history', [ImportsController::class, 'history']);
    Route::get('/{id}', [ImportsController::class, 'find']);
});

Route::get('/projects', [ProjectsController::class, 'list']);
Route::get('/projects/{id}', [ProjectsController::class, 'find']);
Route::get('/projects/{id}/allocations', [ProjectsController::class, 'allocations']);
Route::get('/projects/{id}/monthly-demand', [ProjectsController::class, 'monthlyDemand']);
Route::get('/projects/{id}/financials', [ProjectsController::class, 'financials']);
Route::get('/projects/{id}/gap', [ProjectsController::class, 'gap']);
Route::get('/projects/{id}/ai-recommendations', [ProjectsController::class, 'aiRecommendations']);
Route::post('/projects/{id}/ai-recommendations', [ProjectsController::class, 'createAiRecommendation']);
Route::post('/projects/{id}/ai-recommendations/generate', [ProjectsController::class, 'generateAiRecommendation']);

Route::get('/staff', [StaffAllocationsController::class, 'listStaff']);
Route::get('/staff/{id}', [StaffAllocationsController::class, 'findStaff']);
Route::post('/staff', [StaffAllocationsController::class, 'createStaff']);
Route::post('/staff/{id}/availability', [StaffAllocationsController::class, 'updateAvailability']);

Route::post('/allocations/warnings', [StaffAllocationsController::class, 'warnings']);
Route::post('/allocations', [StaffAllocationsController::class, 'createAllocation']);
Route::post('/allocations/{id}', [StaffAllocationsController::class, 'updateAllocation']);
Route::post('/allocations/{id}/delete', [StaffAllocationsController::class, 'deleteAllocation']);

Route::get('/dashboards/role-gap', [InsightsController::class, 'roleGap']);
Route::get('/dashboards/monthly-demand', [InsightsController::class, 'monthlyDemand']);
Route::get('/dashboards/financial-summary', [InsightsController::class, 'financialSummary']);
Route::get('/reports/ai-summary', [InsightsController::class, 'aiSummary']);
Route::get('/exports/role-gap.csv', [InsightsController::class, 'roleGapCsv']);
Route::get('/exports/monthly-demand.csv', [InsightsController::class, 'monthlyDemandCsv']);

Route::get('/scenarios', [ScenarioMapsController::class, 'scenarios']);
Route::post('/scenarios', [ScenarioMapsController::class, 'createScenario']);
Route::get('/scenarios/{id}', [ScenarioMapsController::class, 'findScenario']);
Route::post('/scenarios/{id}/projects', [ScenarioMapsController::class, 'upsertProject']);
Route::get('/scenarios/{id}/analysis', [ScenarioMapsController::class, 'analysis']);

Route::get('/maps/projects', [ScenarioMapsController::class, 'mapProjects']);
Route::get('/maps/staff', [ScenarioMapsController::class, 'mapStaff']);
Route::get('/maps/heatmap', [ScenarioMapsController::class, 'heatmap']);
Route::get('/maps/shared-resource-suggestions', [ScenarioMapsController::class, 'sharedResourceSuggestions']);

Route::get('/settings/resource-roles', [SettingsAuditController::class, 'resourceRoles']);
Route::get('/settings/resource-rules', [SettingsAuditController::class, 'resourceRules']);
Route::post('/settings/resource-rules', [SettingsAuditController::class, 'upsertResourceRule']);
Route::get('/settings/phase-multipliers', [SettingsAuditController::class, 'phaseMultipliers']);
Route::post('/settings/phase-multipliers', [SettingsAuditController::class, 'upsertPhaseMultiplier']);
Route::get('/settings/complexity-multipliers', [SettingsAuditController::class, 'complexityMultipliers']);
Route::post('/settings/complexity-multipliers', [SettingsAuditController::class, 'upsertComplexityMultiplier']);
Route::get('/settings/ai-prompts', [SettingsAuditController::class, 'aiPrompts']);
Route::post('/settings/ai-prompts', [SettingsAuditController::class, 'upsertAiPrompt']);

Route::get('/audit-logs', [SettingsAuditController::class, 'auditLogs']);
