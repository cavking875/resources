<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Security\RateLimiter;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class AuthController
{
    public function login(Request $request): JsonResponse
    {
        $payload = $this->decodedJson($request);
        if ($payload === null) {
            return response()->json(['error' => 'Invalid JSON request body.'], 400);
        }

        try {
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $rateKey = 'auth:login:' . $this->clientIp($request) . ':' . $email;

            $limiter = new RateLimiter();
            $state = $limiter->check($rateKey, 12, 900);
            if (($state['allowed'] ?? false) !== true) {
                return response()->json([
                    'error' => 'Too many requests. Please retry later.',
                    'retry_after_seconds' => (int) ($state['retry_after_seconds'] ?? 900),
                ], 429);
            }

            $data = (new AuthService())->login($payload);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $token = trim((string) $request->bearerToken());
            $data = (new AuthService())->logout($token);

            return response()->json(['data' => $data]);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    private function decodedJson(Request $request): ?array
    {
        $raw = (string) $request->getContent();
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function clientIp(Request $request): string
    {
        $xff = trim((string) $request->header('X-Forwarded-For', ''));
        if ($xff !== '') {
            $parts = array_map('trim', explode(',', $xff));
            if ($parts !== [] && $parts[0] !== '') {
                return $parts[0];
            }
        }

        $xri = trim((string) $request->header('X-Real-IP', ''));
        if ($xri !== '') {
            return $xri;
        }

        $remote = trim((string) $request->ip());

        return $remote !== '' ? $remote : 'unknown';
    }
}
