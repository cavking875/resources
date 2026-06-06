<?php

declare(strict_types=1);

namespace App\Finance;

use InvalidArgumentException;
use PDO;

final class FinancialModelService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function projectFinancials(int $projectId, float $targetPercent = 8.0): array
    {
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id must be a positive integer.');
        }
        if ($targetPercent <= 0) {
            throw new InvalidArgumentException('target_percent must be greater than zero.');
        }

        $project = $this->projectById($projectId);
        if ($project === null) {
            throw new InvalidArgumentException('Project not found.');
        }

        $breakdown = $this->costBreakdownForProject($projectId);

        $projectValue = (float) ($project['project_value'] ?? 0);
        $resourceCost = round((float) ($breakdown['total_cost'] ?? 0.0), 2);
        $resourcePercent = $projectValue > 0 ? round(($resourceCost / $projectValue) * 100, 2) : 0.0;
        $contractorCost = round((float) ($breakdown['contractor_cost'] ?? 0.0), 2);
        $permanentCost = round((float) ($breakdown['permanent_cost'] ?? 0.0), 2);

        $marginWarning = $resourcePercent > $targetPercent
            ? sprintf(
                'Forecast resource cost is %.2f%% of project value (target %.2f%%). Review staffing assumptions or margin.',
                $resourcePercent,
                $targetPercent
            )
            : null;

        return [
            'project_id' => $projectId,
            'project_name' => (string) ($project['project_name'] ?? ''),
            'project_value' => round($projectValue, 2),
            'resource_cost' => $resourceCost,
            'resource_percent' => $resourcePercent,
            'target_percent' => $targetPercent,
            'margin_warning' => $marginWarning,
            'cost_split' => [
                'permanent_cost' => $permanentCost,
                'contractor_cost' => $contractorCost,
            ],
        ];
    }

    public function portfolioSummary(float $targetPercent = 8.0): array
    {
        if ($targetPercent <= 0) {
            throw new InvalidArgumentException('target_percent must be greater than zero.');
        }

        $stmt = $this->pdo->query(
            'SELECT p.id, p.project_name, p.project_value,
                    COALESCE(SUM(
                        (DATEDIFF(ra.end_date, ra.start_date) + 1)
                        * ra.allocation_fte
                        * COALESCE(st.day_rate, st.salary_cost / 260)
                    ), 0) AS total_resource_cost,
                    COALESCE(SUM(
                        CASE WHEN st.employment_type = "contractor" THEN
                            (DATEDIFF(ra.end_date, ra.start_date) + 1)
                            * ra.allocation_fte
                            * COALESCE(st.day_rate, st.salary_cost / 260)
                        ELSE 0 END
                    ), 0) AS contractor_cost,
                    COALESCE(SUM(
                        CASE WHEN st.employment_type = "permanent" THEN
                            (DATEDIFF(ra.end_date, ra.start_date) + 1)
                            * ra.allocation_fte
                            * COALESCE(st.day_rate, st.salary_cost / 260)
                        ELSE 0 END
                    ), 0) AS permanent_cost
             FROM projects p
             LEFT JOIN resource_allocations ra ON ra.project_id = p.id
             LEFT JOIN staff st ON st.id = ra.staff_id
             GROUP BY p.id, p.project_name, p.project_value
             ORDER BY p.id DESC'
        );
        $rows = $stmt->fetchAll();

        $projectRows = [];
        $portfolioValue = 0.0;
        $portfolioCost = 0.0;
        $portfolioContractor = 0.0;
        $portfolioPermanent = 0.0;

        foreach ($rows as $row) {
            $projectValue = (float) ($row['project_value'] ?? 0.0);
            $resourceCost = round((float) ($row['total_resource_cost'] ?? 0.0), 2);
            $resourcePercent = $projectValue > 0 ? round(($resourceCost / $projectValue) * 100, 2) : 0.0;
            $contractorCost = round((float) ($row['contractor_cost'] ?? 0.0), 2);
            $permanentCost = round((float) ($row['permanent_cost'] ?? 0.0), 2);

            $projectRows[] = [
                'project_id' => (int) $row['id'],
                'project_name' => (string) $row['project_name'],
                'project_value' => round($projectValue, 2),
                'resource_cost' => $resourceCost,
                'resource_percent' => $resourcePercent,
                'over_target' => $resourcePercent > $targetPercent,
                'contractor_cost' => $contractorCost,
                'permanent_cost' => $permanentCost,
            ];

            $portfolioValue += $projectValue;
            $portfolioCost += $resourceCost;
            $portfolioContractor += $contractorCost;
            $portfolioPermanent += $permanentCost;
        }

        $portfolioPercent = $portfolioValue > 0 ? round(($portfolioCost / $portfolioValue) * 100, 2) : 0.0;

        return [
            'target_percent' => $targetPercent,
            'portfolio_value' => round($portfolioValue, 2),
            'portfolio_resource_cost' => round($portfolioCost, 2),
            'portfolio_resource_percent' => $portfolioPercent,
            'portfolio_cost_split' => [
                'permanent_cost' => round($portfolioPermanent, 2),
                'contractor_cost' => round($portfolioContractor, 2),
            ],
            'margin_warning' => $portfolioPercent > $targetPercent
                ? sprintf(
                    'Portfolio resource cost is %.2f%% of project value (target %.2f%%).',
                    $portfolioPercent,
                    $targetPercent
                )
                : null,
            'projects' => $projectRows,
        ];
    }

    private function projectById(int $projectId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, project_name, project_value
             FROM projects
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $projectId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    private function costBreakdownForProject(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                COALESCE(SUM(
                    (DATEDIFF(ra.end_date, ra.start_date) + 1)
                    * ra.allocation_fte
                    * COALESCE(st.day_rate, st.salary_cost / 260)
                ), 0) AS total_cost,
                COALESCE(SUM(
                    CASE WHEN st.employment_type = "contractor" THEN
                        (DATEDIFF(ra.end_date, ra.start_date) + 1)
                        * ra.allocation_fte
                        * COALESCE(st.day_rate, st.salary_cost / 260)
                    ELSE 0 END
                ), 0) AS contractor_cost,
                COALESCE(SUM(
                    CASE WHEN st.employment_type = "permanent" THEN
                        (DATEDIFF(ra.end_date, ra.start_date) + 1)
                        * ra.allocation_fte
                        * COALESCE(st.day_rate, st.salary_cost / 260)
                    ELSE 0 END
                ), 0) AS permanent_cost
             FROM resource_allocations ra
             INNER JOIN staff st ON st.id = ra.staff_id
             WHERE ra.project_id = :project_id'
        );
        $stmt->execute([':project_id' => $projectId]);
        $row = $stmt->fetch();

        return $row === false ? [
            'total_cost' => 0.0,
            'contractor_cost' => 0.0,
            'permanent_cost' => 0.0,
        ] : $row;
    }
}
