<?php

declare(strict_types=1);

namespace App\Dashboard;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class ProjectMonthlyDemandService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forProject(int $projectId, ?string $startMonth = null, int $months = 12): array
    {
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id must be a positive integer.');
        }
        if ($months < 1 || $months > 24) {
            throw new InvalidArgumentException('months must be between 1 and 24.');
        }

        $start = new DateTimeImmutable($this->normalizeMonth($startMonth));
        $end = $start->modify('+' . ($months - 1) . ' months');

        $stmt = $this->pdo->prepare(
            'SELECT DATE_FORMAT(rf.month, "%Y-%m") AS month,
                    rr.role_name,
                    SUM(rf.required_fte) AS required_fte
             FROM resource_forecasts rf
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             WHERE rf.project_id = :project_id
               AND rf.month BETWEEN :start_month AND :end_month
             GROUP BY DATE_FORMAT(rf.month, "%Y-%m"), rr.role_name
             ORDER BY month ASC, rr.role_name ASC'
        );
        $stmt->execute([
            ':project_id' => $projectId,
            ':start_month' => $start->format('Y-m-01'),
            ':end_month' => $end->format('Y-m-01'),
        ]);

        $rows = $stmt->fetchAll();
        $byMonth = [];
        foreach ($rows as $row) {
            $month = (string) $row['month'];
            if (!isset($byMonth[$month])) {
                $byMonth[$month] = [
                    'month' => $month,
                    'total_required_fte' => 0.0,
                    'roles' => [],
                ];
            }

            $value = round((float) $row['required_fte'], 2);
            $byMonth[$month]['roles'][] = [
                'role' => (string) $row['role_name'],
                'required_fte' => $value,
            ];
            $byMonth[$month]['total_required_fte'] = round($byMonth[$month]['total_required_fte'] + $value, 2);
        }

        return [
            'project_id' => $projectId,
            'start_month' => $start->format('Y-m'),
            'end_month' => $end->format('Y-m'),
            'months' => $months,
            'rows' => array_values($byMonth),
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

        throw new InvalidArgumentException('start_month must be Y-m or Y-m-d format.');
    }
}
