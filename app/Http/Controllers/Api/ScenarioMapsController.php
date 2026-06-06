<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class ScenarioMapsController
{
    public function scenarios(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Scenario/ScenarioService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->scenarios();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function createScenario(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Scenario/ScenarioService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->createScenario($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function findScenario(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Scenario/ScenarioService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->findScenario($id);
            if ($data === null) {
                return response()->json(['error' => 'Scenario not found.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function upsertProject(Request $request, int $id): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Scenario/ScenarioService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->upsertScenarioProject($id, $payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function analysis(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Scenario/ScenarioService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->scenarioAnalysis(
                $id,
                $request->query('month') !== null ? (string) $request->query('month') : null
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function mapProjects(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Location/LocationIntelligenceService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->projectMap();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function mapStaff(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Location/LocationIntelligenceService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->staffMap();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function heatmap(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Location/LocationIntelligenceService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->heatmap($request->query('month') !== null ? (string) $request->query('month') : null);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function sharedResourceSuggestions(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ScenarioMapsController.php');
        LegacyBridge::requireLegacy('Location/LocationIntelligenceService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ScenarioMapsController($pdo);
            $data = $legacy->sharedResourceSuggestions(
                $request->query('role_id') !== null ? (int) $request->query('role_id') : null,
                (float) $request->query('radius_miles', 20.0),
                $request->query('month') !== null ? (string) $request->query('month') : null
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
