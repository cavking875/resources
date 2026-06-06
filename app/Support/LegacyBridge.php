<?php

declare(strict_types=1);

namespace App\Support;

use App\Database\ConnectionFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

final class LegacyBridge
{
    public static function requireLegacy(string $relativePath): void
    {
        $candidates = [
            dirname(__DIR__, 2) . '/src',
            dirname(__DIR__, 3) . '/src',
        ];

        $legacyRoot = null;
        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                $legacyRoot = $candidate;
                break;
            }
        }

        if ($legacyRoot === null) {
            throw new RuntimeException('Legacy source directory not found.');
        }

        require_once $legacyRoot . '/' . ltrim($relativePath, '/');
    }

    public static function pdo(): PDO
    {
        $connection = (string) (getenv('DB_CONNECTION') ?: '');
        if ($connection === 'sqlite') {
            return DB::connection()->getPdo();
        }

        self::requireLegacy('Database/ConnectionFactory.php');

        return ConnectionFactory::fromEnvironment();
    }

    public static function clientIp(Request $request): string
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

    public static function jsonPayload(Request $request): array
    {
        $raw = (string) $request->getContent();
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function decodedJson(Request $request): ?array
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
}
