<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Support\LegacyBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class ForecastController
{
    public function forecast(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ForecastController.php');
        LegacyBridge::requireLegacy('Forecast/ForecastEngine.php');
        LegacyBridge::requireLegacy('Forecast/ForecastPersistenceService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $legacy = new \App\Http\ForecastController($pdo);
            $data = $legacy->forecast($payload);

            return response()->json(['data' => $data]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function persist(Request $request): JsonResponse
    {
        $payload = LegacyBridge::decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        LegacyBridge::requireLegacy('Http/ForecastController.php');
        LegacyBridge::requireLegacy('Forecast/ForecastEngine.php');
        LegacyBridge::requireLegacy('Forecast/ForecastPersistenceService.php');
        LegacyBridge::requireLegacy('Auth/AuthService.php');

        $pdo = LegacyBridge::pdo();

        try {
            $auth = new \App\Auth\AuthService($pdo);
            $user = $auth->authenticateToken((string) $request->bearerToken());
            $auth->authorizeRole($user, ['Admin', 'Resource Manager', 'Project Manager']);

            $legacy = new \App\Http\ForecastController($pdo);
            $data = $legacy->persist($payload);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
