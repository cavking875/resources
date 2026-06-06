<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use InvalidArgumentException;

final class InsightsController
{
    public function roleGap(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/InsightsController.php');
        LegacyBridge::requireLegacy('Dashboard/RoleGapService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\InsightsController($pdo);
            $data = $legacy->roleGap($request->query('month') !== null ? (string) $request->query('month') : null);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function monthlyDemand(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/InsightsController.php');
        LegacyBridge::requireLegacy('Dashboard/MonthlyDemandService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\InsightsController($pdo);
            $data = $legacy->monthlyDemand(
                $request->query('start_month') !== null ? (string) $request->query('start_month') : null,
                (int) $request->query('months', 12)
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function aiSummary(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/InsightsController.php');
        LegacyBridge::requireLegacy('Report/AiSummaryService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager', 'Viewer']);

            $legacy = new \App\Http\InsightsController($pdo);
            $data = $legacy->aiSummary(
                $request->query('start_month') !== null ? (string) $request->query('start_month') : null,
                (int) $request->query('months', 12)
            );

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function financialSummary(Request $request): JsonResponse
    {
        LegacyBridge::requireLegacy('Http/InsightsController.php');
        LegacyBridge::requireLegacy('Finance/FinancialModelService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Commercial Manager']);

            $legacy = new \App\Http\InsightsController($pdo);
            $data = $legacy->financialSummary((float) $request->query('target_percent', 8.0));

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function roleGapCsv(Request $request)
    {
        LegacyBridge::requireLegacy('Http/InsightsController.php');
        LegacyBridge::requireLegacy('Report/CsvExportService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager']);

            $month = $request->query('month') !== null ? (string) $request->query('month') : null;
            $legacy = new \App\Http\InsightsController($pdo);
            $csv = $legacy->roleGapCsv($month);

            $monthLabel = $month !== null && trim($month) !== '' ? trim($month) : date('Y-m');

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="role-gap-' . $monthLabel . '.csv"',
            ]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function monthlyDemandCsv(Request $request)
    {
        LegacyBridge::requireLegacy('Http/InsightsController.php');
        LegacyBridge::requireLegacy('Report/CsvExportService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();
        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager', 'Commercial Manager']);

            $startMonth = $request->query('start_month') !== null ? (string) $request->query('start_month') : null;
            $months = (int) $request->query('months', 12);
            $legacy = new \App\Http\InsightsController($pdo);
            $csv = $legacy->monthlyDemandCsv($startMonth, $months);

            $startLabel = $startMonth !== null && trim($startMonth) !== '' ? trim($startMonth) : date('Y-m');

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="monthly-demand-' . $startLabel . '-' . $months . 'm.csv"',
            ]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
