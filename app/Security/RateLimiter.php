<?php

declare(strict_types=1);

namespace App\Security;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class RateLimiter
{
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
        $windowStart = $this->floorWindowStart($now, $windowSeconds)->format('Y-m-d H:i:s');

        $attempts = DB::transaction(function () use ($key, $windowStart): int {
            $updated = DB::table('api_rate_limits')
                ->where('rate_key', $key)
                ->where('window_start', $windowStart)
                ->increment('attempts');

            if ($updated === 0) {
                try {
                    DB::table('api_rate_limits')->insert([
                        'rate_key' => $key,
                        'window_start' => $windowStart,
                        'attempts' => 1,
                    ]);
                } catch (\Throwable) {
                    DB::table('api_rate_limits')
                        ->where('rate_key', $key)
                        ->where('window_start', $windowStart)
                        ->increment('attempts');
                }
            }

            $row = DB::table('api_rate_limits')
                ->where('rate_key', $key)
                ->where('window_start', $windowStart)
                ->select('attempts')
                ->first();

            return (int) ($row->attempts ?? 0);
        });

        $allowed = $attempts <= $maxAttempts;
        $remaining = max(0, $maxAttempts - $attempts);
        $retryAfter = max(1, ($this->floorWindowStart($now, $windowSeconds)->getTimestamp() + $windowSeconds) - $now->getTimestamp());

        DB::table('api_rate_limits')
            ->where('window_start', '<', $now->modify('-1 day')->format('Y-m-d H:i:s'))
            ->delete();

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
}
