<?php

declare(strict_types=1);

namespace App\Dashboard;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class RoleGapService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forMonth(?string $month = null): array
    {
        $monthDate = $this->normalizeMonth($month);

        $required = $this->requiredByRole($monthDate);
        $available = $this->availableByRole();

        $allRoles = array_unique(array_merge(array_keys($required), array_keys($available)));
        sort($allRoles);

        $rows = [];
        foreach ($allRoles as $roleName) {
            $requiredFte = round((float) ($required[$roleName] ?? 0.0), 2);
            $availableFte = round((float) ($available[$roleName] ?? 0.0), 2);
            $gap = round($availableFte - $requiredFte, 2);

            $rows[] = [
                'role' => $roleName,
                'required_fte' => $requiredFte,
                'current_fte' => $availableFte,
                'gap_fte' => $gap,
                'status' => $gap < 0 ? 'under' : ($gap > 0 ? 'over' : 'balanced'),
            ];
        }

        return [
            'month' => (new DateTimeImmutable($monthDate))->format('Y-m'),
            'rows' => $rows,
        ];
    }

    private function normalizeMonth(?string $month): string
    {
        if ($month === null || trim($month) === '') {
            return (new DateTimeImmutable('first day of this month'))->format('Y-m-01');
        }

        $month = trim($month);
        $accepted = DateTimeImmutable::createFromFormat('Y-m', $month);
        if ($accepted instanceof DateTimeImmutable) {
            return $accepted->format('Y-m-01');
        }

        $accepted = DateTimeImmutable::createFromFormat('Y-m-d', $month);
        if ($accepted instanceof DateTimeImmutable) {
            return $accepted->format('Y-m-01');
        }

        throw new InvalidArgumentException('month must be Y-m or Y-m-d format.');
    }

    private function requiredByRole(string $monthDate): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rr.role_name, SUM(rf.required_fte) AS total_required
             FROM resource_forecasts rf
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             WHERE rf.month = :month
             GROUP BY rr.role_name'
        );
        $stmt->execute([':month' => $monthDate]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role_name']] = (float) $row['total_required'];
        }

        return $result;
    }

    private function availableByRole(): array
    {
        $stmt = $this->pdo->query(
            'SELECT rr.role_name, SUM(st.max_fte) AS total_available
             FROM staff st
             INNER JOIN resource_roles rr ON rr.id = st.role_id
             WHERE st.active = 1
             GROUP BY rr.role_name'
        );
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role_name']] = (float) $row['total_available'];
        }

        return $result;
    }
}
