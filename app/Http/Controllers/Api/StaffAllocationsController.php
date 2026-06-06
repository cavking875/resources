<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class StaffAllocationsController
{
    public function listStaff(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Staff/StaffRegisterService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->listStaff($request->query());

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function findStaff(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Staff/StaffRegisterService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->findStaff($id);
            if ($data === null) {
                return response()->json(['error' => 'Staff record not found.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function createStaff(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Staff/StaffRegisterService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->createStaff($payload);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateAvailability(Request $request, int $id): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Staff/StaffRegisterService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->updateStaffAvailability($id, $payload);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function warnings(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Allocation/AllocationWarningService.php');

        $pdo = LegacyBridge::pdo();
        $legacy = new \App\Http\StaffAllocationsController($pdo);
        $data = $legacy->warnings($payload);

        return response()->json(['data' => $data]);
    }

    public function createAllocation(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Allocation/AllocationService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->createAllocation($payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateAllocation(Request $request, int $id): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Allocation/AllocationService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->updateAllocation($id, $payload, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function deleteAllocation(Request $request, int $id): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/StaffAllocationsController.php');
        LegacyBridge::requireLegacy('Allocation/AllocationService.php');
        LegacyBridge::requireLegacy('Audit/AuditLogService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager']);

            $legacy = new \App\Http\StaffAllocationsController($pdo);
            $data = $legacy->deleteAllocation($id, (int) ($user['id'] ?? 0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
