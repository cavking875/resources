<?php

declare(strict_types=1);

namespace App\Project;

use PDO;

final class ProjectRegisterService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(200, max(1, (int) ($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (isset($filters['region']) && trim((string) $filters['region']) !== '') {
            $where[] = 'p.region = :region';
            $params[':region'] = trim((string) $filters['region']);
        }

        if (isset($filters['project_stage']) && trim((string) $filters['project_stage']) !== '') {
            $where[] = 'p.project_stage = :project_stage';
            $params[':project_stage'] = trim((string) $filters['project_stage']);
        }

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $where[] = '(p.project_name LIKE :search OR p.client_name LIKE :search OR p.location LIKE :search)';
            $params[':search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM projects p {$whereSql}");
        $countStmt->execute($params);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $sql = "
            SELECT p.id, p.project_name, p.client_name, p.location, p.region, p.project_value,
                   p.start_date, p.end_date, p.project_stage, p.project_type, p.contract_type,
                   p.delivery_model, p.complexity_level, p.risk_level, p.created_at
            FROM projects p
            {$whereSql}
            ORDER BY p.start_date ASC, p.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'rows' => $stmt->fetchAll(),
        ];
    }

    public function find(int $projectId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*
             FROM projects p
             WHERE p.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $projectId]);
        $project = $stmt->fetch();
        if (!$project) {
            return null;
        }

        $rolesStmt = $this->pdo->prepare(
            'SELECT rr.role_name, SUM(rf.required_fte) AS total_required_fte
             FROM resource_forecasts rf
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             WHERE rf.project_id = :project_id
             GROUP BY rr.role_name
             ORDER BY rr.role_name ASC'
        );
        $rolesStmt->execute([':project_id' => $projectId]);

        $allocStmt = $this->pdo->prepare(
            'SELECT rr.role_name, SUM(ra.allocation_fte) AS total_allocated_fte
             FROM resource_allocations ra
             INNER JOIN resource_roles rr ON rr.id = ra.role_id
             WHERE ra.project_id = :project_id
             GROUP BY rr.role_name
             ORDER BY rr.role_name ASC'
        );
        $allocStmt->execute([':project_id' => $projectId]);

        return [
            'project' => $project,
            'required_by_role' => $rolesStmt->fetchAll(),
            'allocated_by_role' => $allocStmt->fetchAll(),
        ];
    }
}
