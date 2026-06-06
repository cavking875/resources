<?php

declare(strict_types=1);

namespace App\Scenario;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class ScenarioService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(): array
    {
        $stmt = $this->pdo->query(
            'SELECT s.id, s.scenario_name, s.created_by, u.name AS created_by_name, s.base_case, s.created_at
             FROM scenarios s
             LEFT JOIN users u ON u.id = s.created_by
             ORDER BY s.id DESC'
        );

        return $stmt->fetchAll();
    }

    public function create(string $scenarioName, ?int $createdBy = null, bool $baseCase = false): array
    {
        $scenarioName = trim($scenarioName);
        if ($scenarioName === '') {
            throw new InvalidArgumentException('scenario_name is required.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO scenarios (scenario_name, created_by, base_case)
             VALUES (:scenario_name, :created_by, :base_case)'
        );
        $stmt->execute([
            ':scenario_name' => $scenarioName,
            ':created_by' => $createdBy,
            ':base_case' => $baseCase ? 1 : 0,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'action' => 'created',
        ];
    }

    public function find(int $scenarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.scenario_name, s.created_by, u.name AS created_by_name, s.base_case, s.created_at
             FROM scenarios s
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $scenarioId]);
        $scenario = $stmt->fetch();
        if (!$scenario) {
            return null;
        }

        $projStmt = $this->pdo->prepare(
            'SELECT sp.id, sp.project_id, p.project_name, sp.adjusted_value,
                    sp.adjusted_start_date, sp.adjusted_end_date, sp.included
             FROM scenario_projects sp
             INNER JOIN projects p ON p.id = sp.project_id
             WHERE sp.scenario_id = :scenario_id
             ORDER BY sp.id DESC'
        );
        $projStmt->execute([':scenario_id' => $scenarioId]);

        return [
            'scenario' => $scenario,
            'projects' => $projStmt->fetchAll(),
        ];
    }

    public function upsertProjectAdjustment(int $scenarioId, array $payload): array
    {
        if ($scenarioId <= 0) {
            throw new InvalidArgumentException('scenario_id must be a positive integer.');
        }

        $projectId = (int) ($payload['project_id'] ?? 0);
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id is required.');
        }

        $adjustedValue = $payload['adjusted_value'] ?? null;
        $adjustedStartDate = $this->nullableDate($payload['adjusted_start_date'] ?? null);
        $adjustedEndDate = $this->nullableDate($payload['adjusted_end_date'] ?? null);
        $included = isset($payload['included']) ? (bool) $payload['included'] : true;

        $existingStmt = $this->pdo->prepare(
            'SELECT id
             FROM scenario_projects
             WHERE scenario_id = :scenario_id AND project_id = :project_id
             LIMIT 1'
        );
        $existingStmt->execute([
            ':scenario_id' => $scenarioId,
            ':project_id' => $projectId,
        ]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            $stmt = $this->pdo->prepare(
                'UPDATE scenario_projects
                 SET adjusted_value = :adjusted_value,
                     adjusted_start_date = :adjusted_start_date,
                     adjusted_end_date = :adjusted_end_date,
                     included = :included
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id' => (int) $existing['id'],
                ':adjusted_value' => $adjustedValue,
                ':adjusted_start_date' => $adjustedStartDate,
                ':adjusted_end_date' => $adjustedEndDate,
                ':included' => $included ? 1 : 0,
            ]);

            return [
                'id' => (int) $existing['id'],
                'action' => 'updated',
            ];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO scenario_projects (
                scenario_id, project_id, adjusted_value, adjusted_start_date, adjusted_end_date, included
             ) VALUES (
                :scenario_id, :project_id, :adjusted_value, :adjusted_start_date, :adjusted_end_date, :included
             )'
        );
        $stmt->execute([
            ':scenario_id' => $scenarioId,
            ':project_id' => $projectId,
            ':adjusted_value' => $adjustedValue,
            ':adjusted_start_date' => $adjustedStartDate,
            ':adjusted_end_date' => $adjustedEndDate,
            ':included' => $included ? 1 : 0,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'action' => 'created',
        ];
    }

    public function analysis(int $scenarioId, ?string $month = null): array
    {
        if ($scenarioId <= 0) {
            throw new InvalidArgumentException('scenario_id must be a positive integer.');
        }

        $monthDate = $month === null ? null : $this->normalizeMonth($month);

        $projects = $this->scenarioProjects($scenarioId);
        if ($projects === []) {
            return [
                'scenario_id' => $scenarioId,
                'month' => $monthDate === null ? null : (new DateTimeImmutable($monthDate))->format('Y-m'),
                'base_total_required_fte' => 0.0,
                'scenario_total_required_fte' => 0.0,
                'base_total_resource_cost' => 0.0,
                'scenario_total_resource_cost' => 0.0,
                'role_impacts' => [],
                'project_impacts' => [],
            ];
        }

        $roleBase = [];
        $roleScenario = [];
        $projectImpacts = [];

        $baseTotalRequired = 0.0;
        $scenarioTotalRequired = 0.0;
        $baseTotalCost = 0.0;
        $scenarioTotalCost = 0.0;

        foreach ($projects as $project) {
            $projectId = (int) $project['project_id'];
            $projectValue = (float) ($project['project_value'] ?? 0.0);
            $included = (int) ($project['included'] ?? 1) === 1;

            $factor = 1.0;
            if (!$included) {
                $factor = 0.0;
            } else {
                $adjustedValue = $project['adjusted_value'];
                if ($adjustedValue !== null && $projectValue > 0) {
                    $factor = (float) $adjustedValue / $projectValue;
                }
            }

            $roleRows = $this->projectRoleForecastRows($projectId, $monthDate);
            $projectBaseRequired = 0.0;
            $projectScenarioRequired = 0.0;

            foreach ($roleRows as $row) {
                $role = (string) $row['role_name'];
                $required = (float) $row['required_fte'];
                $scenarioRequired = $required * $factor;

                $roleBase[$role] = ($roleBase[$role] ?? 0.0) + $required;
                $roleScenario[$role] = ($roleScenario[$role] ?? 0.0) + $scenarioRequired;

                $projectBaseRequired += $required;
                $projectScenarioRequired += $scenarioRequired;
            }

            $projectBaseCost = $this->projectResourceCost($projectId);
            $projectScenarioCost = $projectBaseCost * $factor;

            $baseTotalRequired += $projectBaseRequired;
            $scenarioTotalRequired += $projectScenarioRequired;
            $baseTotalCost += $projectBaseCost;
            $scenarioTotalCost += $projectScenarioCost;

            $projectImpacts[] = [
                'project_id' => $projectId,
                'project_name' => (string) $project['project_name'],
                'included' => $included,
                'factor' => round($factor, 4),
                'base_required_fte' => round($projectBaseRequired, 2),
                'scenario_required_fte' => round($projectScenarioRequired, 2),
                'base_resource_cost' => round($projectBaseCost, 2),
                'scenario_resource_cost' => round($projectScenarioCost, 2),
            ];
        }

        $roles = array_unique(array_merge(array_keys($roleBase), array_keys($roleScenario)));
        sort($roles);

        $roleImpacts = [];
        foreach ($roles as $role) {
            $base = round((float) ($roleBase[$role] ?? 0.0), 2);
            $scenario = round((float) ($roleScenario[$role] ?? 0.0), 2);
            $roleImpacts[] = [
                'role' => $role,
                'base_required_fte' => $base,
                'scenario_required_fte' => $scenario,
                'delta_fte' => round($scenario - $base, 2),
            ];
        }

        return [
            'scenario_id' => $scenarioId,
            'month' => $monthDate === null ? null : (new DateTimeImmutable($monthDate))->format('Y-m'),
            'base_total_required_fte' => round($baseTotalRequired, 2),
            'scenario_total_required_fte' => round($scenarioTotalRequired, 2),
            'base_total_resource_cost' => round($baseTotalCost, 2),
            'scenario_total_resource_cost' => round($scenarioTotalCost, 2),
            'role_impacts' => $roleImpacts,
            'project_impacts' => $projectImpacts,
        ];
    }

    private function scenarioProjects(int $scenarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sp.project_id, sp.adjusted_value, sp.adjusted_start_date, sp.adjusted_end_date, sp.included,
                    p.project_name, p.project_value
             FROM scenario_projects sp
             INNER JOIN projects p ON p.id = sp.project_id
             WHERE sp.scenario_id = :scenario_id'
        );
        $stmt->execute([':scenario_id' => $scenarioId]);
        return $stmt->fetchAll();
    }

    private function projectRoleForecastRows(int $projectId, ?string $monthDate): array
    {
        if ($monthDate === null) {
            $stmt = $this->pdo->prepare(
                'SELECT rr.role_name, AVG(rf.required_fte) AS required_fte
                 FROM resource_forecasts rf
                 INNER JOIN resource_roles rr ON rr.id = rf.role_id
                 WHERE rf.project_id = :project_id
                 GROUP BY rr.role_name'
            );
            $stmt->execute([':project_id' => $projectId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT rr.role_name, SUM(rf.required_fte) AS required_fte
                 FROM resource_forecasts rf
                 INNER JOIN resource_roles rr ON rr.id = rf.role_id
                 WHERE rf.project_id = :project_id
                   AND rf.month = :month
                 GROUP BY rr.role_name'
            );
            $stmt->execute([
                ':project_id' => $projectId,
                ':month' => $monthDate,
            ]);
        }

        return $stmt->fetchAll();
    }

    private function projectResourceCost(int $projectId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(
                (DATEDIFF(ra.end_date, ra.start_date) + 1)
                * ra.allocation_fte
                * COALESCE(st.day_rate, st.salary_cost / 260)
            ), 0) AS total_cost
             FROM resource_allocations ra
             INNER JOIN staff st ON st.id = ra.staff_id
             WHERE ra.project_id = :project_id'
        );
        $stmt->execute([':project_id' => $projectId]);
        $row = $stmt->fetch();

        return (float) ($row['total_cost'] ?? 0.0);
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

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $str);
        if (!$dt) {
            throw new InvalidArgumentException('Dates must use Y-m-d format.');
        }

        return $dt->format('Y-m-d');
    }
}
