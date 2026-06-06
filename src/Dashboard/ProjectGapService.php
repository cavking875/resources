<?php

declare(strict_types=1);

namespace App\Dashboard;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class ProjectGapService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function byProject(int $projectId, ?string $month = null): array
    {
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id must be a positive integer.');
        }

        $monthDate = $month === null ? null : $this->normalizeMonth($month);

        $required = $this->requiredByRole($projectId, $monthDate);
        $allocated = $this->allocatedByRole($projectId, $monthDate);

        $allRoles = array_unique(array_merge(array_keys($required), array_keys($allocated)));
        sort($allRoles);

        $rows = [];
        foreach ($allRoles as $roleName) {
            $requiredFte = round((float) ($required[$roleName] ?? 0.0), 2);
            $allocatedFte = round((float) ($allocated[$roleName] ?? 0.0), 2);
            $gap = round($allocatedFte - $requiredFte, 2);

            $rows[] = [
                'role' => $roleName,
                'required_fte' => $requiredFte,
                'allocated_fte' => $allocatedFte,
                'gap_fte' => $gap,
                'status' => $gap < 0 ? 'under' : ($gap > 0 ? 'over' : 'balanced'),
            ];
        }

        return [
            'project_id' => $projectId,
            'month' => $monthDate === null ? null : (new DateTimeImmutable($monthDate))->format('Y-m'),
            'rows' => $rows,
        ];
    }

    private function normalizeMonth(string $month): string
    {
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

    private function requiredByRole(int $projectId, ?string $monthDate): array
    {
        if ($monthDate === null) {
            $stmt = $this->pdo->prepare(
                'SELECT rr.role_name, AVG(rf.required_fte) AS total_required
                 FROM resource_forecasts rf
                 INNER JOIN resource_roles rr ON rr.id = rf.role_id
                 WHERE rf.project_id = :project_id
                 GROUP BY rr.role_name'
            );
            $stmt->execute([':project_id' => $projectId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT rr.role_name, SUM(rf.required_fte) AS total_required
                 FROM resource_forecasts rf
                 INNER JOIN resource_roles rr ON rr.id = rf.role_id
                 WHERE rf.project_id = :project_id AND rf.month = :month
                 GROUP BY rr.role_name'
            );
            $stmt->execute([':project_id' => $projectId, ':month' => $monthDate]);
        }

        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role_name']] = (float) $row['total_required'];
        }

        return $result;
    }

    private function allocatedByRole(int $projectId, ?string $monthDate): array
    {
        if ($monthDate === null) {
            $stmt = $this->pdo->prepare(
                'SELECT rr.role_name, SUM(ra.allocation_fte) AS total_allocated
                 FROM resource_allocations ra
                 INNER JOIN resource_roles rr ON rr.id = ra.role_id
                 WHERE ra.project_id = :project_id
                 GROUP BY rr.role_name'
            );
            $stmt->execute([':project_id' => $projectId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT rr.role_name, SUM(ra.allocation_fte) AS total_allocated
                 FROM resource_allocations ra
                 INNER JOIN resource_roles rr ON rr.id = ra.role_id
                 WHERE ra.project_id = :project_id
                   AND ra.start_date <= LAST_DAY(:month)
                   AND ra.end_date >= :month
                 GROUP BY rr.role_name'
            );
            $stmt->execute([':project_id' => $projectId, ':month' => $monthDate]);
        }

        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role_name']] = (float) $row['total_allocated'];
        }

        return $result;
    }
}
