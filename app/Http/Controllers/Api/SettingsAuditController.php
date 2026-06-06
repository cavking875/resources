<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class SettingsAuditController
{
    public function auditLogs(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Audit/AuditQueryService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->auditLogs($request->query());

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function resourceRoles(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->resourceRoles();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function aiPrompts(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('AI/AiPromptService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->aiPrompts();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function upsertAiPrompt(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('AI/AiPromptService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->upsertAiPrompt($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException | JsonException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function resourceRules(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->resourceRules();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function phaseMultipliers(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->phaseMultipliers();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function complexityMultipliers(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->complexityMultipliers();

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function upsertResourceRule(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->upsertResourceRule($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function upsertPhaseMultiplier(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->upsertPhaseMultiplier($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function upsertComplexityMultiplier(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/SettingsAuditController.php');
        LegacyBridge::requireLegacy('Settings/SettingsService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\SettingsAuditController($pdo);
            $data = $legacy->upsertComplexityMultiplier($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
