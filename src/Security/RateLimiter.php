<?php

declare(strict_types=1);

namespace App\Security;

use DateTimeImmutable;
use PDO;

final class RateLimiter
{
    public function __construct(private PDO $pdo)
    {
    }

    public function check(string $key, int $maxAttempts, int $windowSeconds): array
    {
        $key = trim($key);
        if ($key === '' || $maxAttempts <= 0 || $windowSeconds <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after_seconds' => $windowSeconds,
                'attempts' => 0,
            ];
        }

        $now = new DateTimeImmutable('now');
        $windowStart = $this->floorWindowStart($now, $windowSeconds);
        $windowStartStr = $windowStart->format('Y-m-d H:i:s');

        $this->increment($key, $windowStartStr);
        $attempts = $this->attempts($key, $windowStartStr);

        $allowed = $attempts <= $maxAttempts;
        $remaining = max(0, $maxAttempts - $attempts);
        $retryAfter = max(1, ($windowStart->getTimestamp() + $windowSeconds) - $now->getTimestamp());

        $this->cleanupOldWindows($now->modify('-1 day')->format('Y-m-d H:i:s'));

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'retry_after_seconds' => $retryAfter,
            'attempts' => $attempts,
        ];
    }

    private function floorWindowStart(DateTimeImmutable $now, int $windowSeconds): DateTimeImmutable
    {
        $timestamp = $now->getTimestamp();
        $bucket = intdiv($timestamp, $windowSeconds) * $windowSeconds;

        return (new DateTimeImmutable('@' . $bucket))->setTimezone($now->getTimezone());
    }

    private function increment(string $key, string $windowStart): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO api_rate_limits (rate_key, window_start, attempts)
             VALUES (:rate_key, :window_start, 1)
             ON DUPLICATE KEY UPDATE attempts = attempts + 1'
        );
        $stmt->execute([
            ':rate_key' => $key,
            ':window_start' => $windowStart,
        ]);
    }

    private function attempts(string $key, string $windowStart): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT attempts
             FROM api_rate_limits
             WHERE rate_key = :rate_key
               AND window_start = :window_start
             LIMIT 1'
        );
        $stmt->execute([
            ':rate_key' => $key,
            ':window_start' => $windowStart,
        ]);
        $row = $stmt->fetch();

        return (int) ($row['attempts'] ?? 0);
    }

    private function cleanupOldWindows(string $cutoff): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM api_rate_limits WHERE window_start < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
    }
}
