<?php

declare(strict_types=1);

namespace App\Allocation;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class AllocationService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listByProject(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ra.id, ra.project_id, p.project_name, ra.staff_id,
                    CONCAT(st.first_name, " ", st.last_name) AS staff_name,
                    ra.role_id, rr.role_name, ra.allocation_fte,
                    ra.start_date, ra.end_date, ra.status, ra.notes
             FROM resource_allocations ra
             INNER JOIN projects p ON p.id = ra.project_id
             INNER JOIN staff st ON st.id = ra.staff_id
             INNER JOIN resource_roles rr ON rr.id = ra.role_id
             WHERE ra.project_id = :project_id
             ORDER BY ra.start_date ASC, ra.id DESC'
        );
        $stmt->execute([':project_id' => $projectId]);

        return [
            'project_id' => $projectId,
            'rows' => $stmt->fetchAll(),
        ];
    }

    public function create(array $payload): array
    {
        $projectId = (int) ($payload['project_id'] ?? 0);
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $roleId = (int) ($payload['role_id'] ?? 0);
        $allocationFte = (float) ($payload['allocation_fte'] ?? 0);
        $startDate = trim((string) ($payload['start_date'] ?? ''));
        $endDate = trim((string) ($payload['end_date'] ?? ''));
        $status = strtolower(trim((string) ($payload['status'] ?? 'proposed')));
        $notes = $this->nullableString($payload['notes'] ?? null);

        if ($projectId <= 0 || $staffId <= 0 || $roleId <= 0) {
            throw new InvalidArgumentException('project_id, staff_id, and role_id are required positive integers.');
        }
        if ($allocationFte <= 0) {
            throw new InvalidArgumentException('allocation_fte must be greater than 0.');
        }
        if (!in_array($status, ['confirmed', 'proposed', 'required'], true)) {
            throw new InvalidArgumentException('status must be confirmed, proposed, or required.');
        }

        $this->assertValidDateRange($startDate, $endDate);

        $warning = $this->overlapWarning($staffId, $allocationFte, $startDate, $endDate);

        $stmt = $this->pdo->prepare(
            'INSERT INTO resource_allocations (
                project_id, staff_id, role_id, allocation_fte, start_date, end_date, status, notes
            ) VALUES (
                :project_id, :staff_id, :role_id, :allocation_fte, :start_date, :end_date, :status, :notes
            )'
        );
        $stmt->execute([
            ':project_id' => $projectId,
            ':staff_id' => $staffId,
            ':role_id' => $roleId,
            ':allocation_fte' => $allocationFte,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':notes' => $notes,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'action' => 'created',
            'warning' => $warning,
        ];
    }

    public function update(int $allocationId, array $payload): array
    {
        if ($allocationId <= 0) {
            throw new InvalidArgumentException('allocation id must be a positive integer.');
        }

        $existing = $this->find($allocationId);
        if ($existing === null) {
            throw new InvalidArgumentException('Allocation not found.');
        }

        $projectId = (int) ($payload['project_id'] ?? $existing['project_id']);
        $staffId = (int) ($payload['staff_id'] ?? $existing['staff_id']);
        $roleId = (int) ($payload['role_id'] ?? $existing['role_id']);
        $allocationFte = (float) ($payload['allocation_fte'] ?? $existing['allocation_fte']);
        $startDate = trim((string) ($payload['start_date'] ?? $existing['start_date']));
        $endDate = trim((string) ($payload['end_date'] ?? $existing['end_date']));
        $status = strtolower(trim((string) ($payload['status'] ?? $existing['status'])));
        $notes = $this->nullableString($payload['notes'] ?? $existing['notes']);

        if ($projectId <= 0 || $staffId <= 0 || $roleId <= 0) {
            throw new InvalidArgumentException('project_id, staff_id, and role_id are required positive integers.');
        }
        if ($allocationFte <= 0) {
            throw new InvalidArgumentException('allocation_fte must be greater than 0.');
        }
        if (!in_array($status, ['confirmed', 'proposed', 'required'], true)) {
            throw new InvalidArgumentException('status must be confirmed, proposed, or required.');
        }

        $this->assertValidDateRange($startDate, $endDate);

        $warning = $this->overlapWarning($staffId, $allocationFte, $startDate, $endDate, $allocationId);

        $stmt = $this->pdo->prepare(
            'UPDATE resource_allocations
             SET project_id = :project_id,
                 staff_id = :staff_id,
                 role_id = :role_id,
                 allocation_fte = :allocation_fte,
                 start_date = :start_date,
                 end_date = :end_date,
                 status = :status,
                 notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $allocationId,
            ':project_id' => $projectId,
            ':staff_id' => $staffId,
            ':role_id' => $roleId,
            ':allocation_fte' => $allocationFte,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':notes' => $notes,
        ]);

        return [
            'id' => $allocationId,
            'action' => 'updated',
            'warning' => $warning,
        ];
    }

    public function delete(int $allocationId): array
    {
        if ($allocationId <= 0) {
            throw new InvalidArgumentException('allocation id must be a positive integer.');
        }

        $stmt = $this->pdo->prepare('DELETE FROM resource_allocations WHERE id = :id');
        $stmt->execute([':id' => $allocationId]);

        return [
            'id' => $allocationId,
            'action' => $stmt->rowCount() > 0 ? 'deleted' : 'not_found',
        ];
    }

    private function overlapWarning(
        int $staffId,
        float $newFte,
        string $startDate,
        string $endDate,
        ?int $excludeAllocationId = null
    ): ?array
    {
        $sql =
            'SELECT SUM(allocation_fte) AS overlapping_fte
             FROM resource_allocations
             WHERE staff_id = :staff_id
               AND start_date <= :new_end
               AND end_date >= :new_start';

        $params = [
            ':staff_id' => $staffId,
            ':new_start' => $startDate,
            ':new_end' => $endDate,
        ];

        if ($excludeAllocationId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeAllocationId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $existingOverlap = (float) (($stmt->fetch()['overlapping_fte'] ?? 0.0));

        $maxStmt = $this->pdo->prepare('SELECT max_fte FROM staff WHERE id = :staff_id LIMIT 1');
        $maxStmt->execute([':staff_id' => $staffId]);
        $maxFte = (float) (($maxStmt->fetch()['max_fte'] ?? 1.0));

        $total = $existingOverlap + $newFte;
        if ($total <= $maxFte) {
            return null;
        }

        return [
            'type' => 'over_allocation',
            'message' => sprintf(
                'Overlapping allocations total %.2f FTE against max %.2f FTE for staff_id %d.',
                $total,
                $maxFte,
                $staffId
            ),
            'overlapping_fte' => round($existingOverlap, 2),
            'new_allocation_fte' => round($newFte, 2),
            'total_fte' => round($total, 2),
            'max_fte' => round($maxFte, 2),
        ];
    }

    public function find(int $allocationId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, project_id, staff_id, role_id, allocation_fte, start_date, end_date, status, notes
             FROM resource_allocations
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $allocationId]);

        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    private function assertValidDateRange(string $startDate, string $endDate): void
    {
        try {
            $start = new DateTimeImmutable($startDate);
            $end = new DateTimeImmutable($endDate);
        } catch (\Exception) {
            throw new InvalidArgumentException('start_date and end_date must be valid dates.');
        }

        if ($start > $end) {
            throw new InvalidArgumentException('start_date must be before or equal to end_date.');
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }
}
