<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class ImportsController
{
    public function validateRows(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ImportController.php');
        LegacyBridge::requireLegacy('Import/CsvProjectValidator.php');

        $pdo = LegacyBridge::pdo();
        $legacy = new \App\Http\ImportController($pdo);
        $data = $legacy->validateRows($payload);

        return response()->json(['data' => $data]);
    }

    public function import(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ImportController.php');
        LegacyBridge::requireLegacy('Import/CsvProjectValidator.php');
        LegacyBridge::requireLegacy('Import/ProjectImportService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager']);

            $legacy = new \App\Http\ImportController($pdo);
            $data = $legacy->import($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function history(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ImportController.php');
        LegacyBridge::requireLegacy('Import/ProjectImportService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ImportController($pdo);
            $data = $legacy->history((int) $request->query('page', 1), (int) $request->query('per_page', 25));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function find(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/ImportController.php');
        LegacyBridge::requireLegacy('Import/ProjectImportService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\ImportController($pdo);
            $data = $legacy->findImport($id);
            if ($data === null) {
                return response()->json(['error' => 'Import record not found.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
