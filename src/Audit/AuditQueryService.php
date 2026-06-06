<?php

declare(strict_types=1);

namespace App\Audit;

use PDO;

final class AuditQueryService
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

        if (isset($filters['entity_type']) && trim((string) $filters['entity_type']) !== '') {
            $where[] = 'al.entity_type = :entity_type';
            $params[':entity_type'] = trim((string) $filters['entity_type']);
        }

        if (isset($filters['action']) && trim((string) $filters['action']) !== '') {
            $where[] = 'al.action = :action';
            $params[':action'] = trim((string) $filters['action']);
        }

        if (isset($filters['user_id']) && (int) $filters['user_id'] > 0) {
            $where[] = 'al.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (isset($filters['from_date']) && trim((string) $filters['from_date']) !== '') {
            $where[] = 'DATE(al.created_at) >= :from_date';
            $params[':from_date'] = trim((string) $filters['from_date']);
        }

        if (isset($filters['to_date']) && trim((string) $filters['to_date']) !== '') {
            $where[] = 'DATE(al.created_at) <= :to_date';
            $params[':to_date'] = trim((string) $filters['to_date']);
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM audit_logs al {$whereSql}");
        $countStmt->execute($params);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = $this->pdo->prepare(
            "SELECT al.id, al.user_id, u.name AS user_name, al.entity_type, al.entity_id,
                    al.action, al.old_values, al.new_values, al.reason, al.created_at
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             {$whereSql}
             ORDER BY al.id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            foreach (['old_values', 'new_values'] as $jsonField) {
                if (is_string($row[$jsonField] ?? null) && $row[$jsonField] !== '') {
                    $decoded = json_decode((string) $row[$jsonField], true);
                    if (is_array($decoded)) {
                        $row[$jsonField] = $decoded;
                    }
                }
            }
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'rows' => $rows,
        ];
    }
}
