<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class ProjectsController
{
    public function list(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('Project/ProjectRegisterService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->list($request->query());

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function find(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('Project/ProjectRegisterService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->find($id);
            if ($data === null) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function allocations(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('Allocation/AllocationService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->allocations($id);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function monthlyDemand(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('Dashboard/ProjectMonthlyDemandService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->monthlyDemand(
                $id,
                $request->query('start_month') !== null ? (string) $request->query('start_month') : null,
                (int) $request->query('months', 12)
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function financials(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('Finance/FinancialModelService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager', 'Project Manager']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->financials($id, (float) $request->query('target_percent', 8.0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function gap(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('Dashboard/ProjectGapService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->gap(
                $id,
                $request->query('month') !== null ? (string) $request->query('month') : null
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function aiRecommendations(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('AI/AiRecommendationService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->aiRecommendations(
                $id,
                $request->query('type') !== null ? (string) $request->query('type') : null
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function createAiRecommendation(Request $request, int $id): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('AI/AiRecommendationService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager']);

            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->createAiRecommendation($id, $payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException | JsonException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function generateAiRecommendation(Request $request, int $id): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ProjectsController.php');
        LegacyBridge::requireLegacy('AI/AiPromptService.php');
        LegacyBridge::requireLegacy('AI/AiRecommendationService.php');
        LegacyBridge::requireLegacy('AI/OpenAiRecommendationGenerator.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Security/RateLimiter.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager']);

            $rateKey = 'ai:generate:user:' . (string) ($user['id'] ?? 0);
            $limiter = new \App\Security\RateLimiter($pdo);
            $state = $limiter->check($rateKey, 30, 3600);
            if (($state['allowed'] ?? false) !== true) {
                return response()->json([
                    'error' => 'Too many requests. Please retry later.',
                    'retry_after_seconds' => (int) ($state['retry_after_seconds'] ?? 3600),
                ], 429);
            }

            $promptType = isset($payload['prompt_type']) ? (string) $payload['prompt_type'] : 'resource_plan';
            $legacy = new \App\Http\ProjectsController($pdo);
            $data = $legacy->generateAiRecommendation($id, $promptType, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException | JsonException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
