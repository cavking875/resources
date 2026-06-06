<?php

declare(strict_types=1);

namespace App\Staff;

use InvalidArgumentException;
use PDO;

final class StaffRegisterService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(array $filters = []): array
    {
        $where = ['st.active = 1'];
        $params = [];

        if (isset($filters['region']) && trim((string) $filters['region']) !== '') {
            $where[] = 'st.region = :region';
            $params[':region'] = trim((string) $filters['region']);
        }

        if (isset($filters['availability_status']) && trim((string) $filters['availability_status']) !== '') {
            $where[] = 'st.availability_status = :availability_status';
            $params[':availability_status'] = trim((string) $filters['availability_status']);
        }

        if (isset($filters['role_id']) && (int) $filters['role_id'] > 0) {
            $where[] = 'st.role_id = :role_id';
            $params[':role_id'] = (int) $filters['role_id'];
        }

        $sql = "
            SELECT st.id, st.first_name, st.last_name, st.region, st.location, st.availability_status,
                   st.max_fte, st.current_fte, st.employment_type, st.day_rate, st.salary_cost,
                   st.charge_out_rate, st.travel_radius_miles, rr.role_name
            FROM staff st
            LEFT JOIN resource_roles rr ON rr.id = st.role_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY st.last_name ASC, st.first_name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    public function find(int $staffId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT st.*, rr.role_name
             FROM staff st
             LEFT JOIN resource_roles rr ON rr.id = st.role_id
             WHERE st.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $staffId]);
        $staff = $stmt->fetch();
        if (!$staff) {
            return null;
        }

        $skillsStmt = $this->pdo->prepare(
            'SELECT skill_name FROM staff_skills WHERE staff_id = :staff_id ORDER BY skill_name ASC'
        );
        $skillsStmt->execute([':staff_id' => $staffId]);

        $allocStmt = $this->pdo->prepare(
            'SELECT ra.id, ra.project_id, p.project_name, ra.role_id, rr.role_name, ra.allocation_fte,
                    ra.start_date, ra.end_date, ra.status
             FROM resource_allocations ra
             INNER JOIN projects p ON p.id = ra.project_id
             INNER JOIN resource_roles rr ON rr.id = ra.role_id
             WHERE ra.staff_id = :staff_id
             ORDER BY ra.start_date ASC, ra.id DESC'
        );
        $allocStmt->execute([':staff_id' => $staffId]);

        return [
            'staff' => $staff,
            'skills' => array_map(static fn (array $r): string => (string) $r['skill_name'], $skillsStmt->fetchAll()),
            'allocations' => $allocStmt->fetchAll(),
        ];
    }

    public function create(array $payload): array
    {
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $employmentType = strtolower(trim((string) ($payload['employment_type'] ?? '')));
        $roleId = isset($payload['role_id']) ? (int) $payload['role_id'] : null;

        if ($firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('first_name and last_name are required.');
        }
        if (!in_array($employmentType, ['permanent', 'contractor'], true)) {
            throw new InvalidArgumentException('employment_type must be permanent or contractor.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO staff (
                first_name, last_name, role_id, location, postcode, latitude, longitude, region,
                employment_type, salary_cost, day_rate, charge_out_rate, availability_status,
                max_fte, current_fte, travel_radius_miles, preferred_projects, certifications,
                start_date, end_date, active
            ) VALUES (
                :first_name, :last_name, :role_id, :location, :postcode, :latitude, :longitude, :region,
                :employment_type, :salary_cost, :day_rate, :charge_out_rate, :availability_status,
                :max_fte, :current_fte, :travel_radius_miles, :preferred_projects, :certifications,
                :start_date, :end_date, 1
            )'
        );

        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':role_id' => $roleId,
            ':location' => $this->nullableString($payload['location'] ?? null),
            ':postcode' => $this->nullableString($payload['postcode'] ?? null),
            ':latitude' => $payload['latitude'] ?? null,
            ':longitude' => $payload['longitude'] ?? null,
            ':region' => $this->nullableString($payload['region'] ?? null),
            ':employment_type' => $employmentType,
            ':salary_cost' => $payload['salary_cost'] ?? null,
            ':day_rate' => $payload['day_rate'] ?? null,
            ':charge_out_rate' => $payload['charge_out_rate'] ?? null,
            ':availability_status' => strtolower(trim((string) ($payload['availability_status'] ?? 'available'))),
            ':max_fte' => $payload['max_fte'] ?? 1.0,
            ':current_fte' => $payload['current_fte'] ?? 0.0,
            ':travel_radius_miles' => $payload['travel_radius_miles'] ?? null,
            ':preferred_projects' => $this->nullableString($payload['preferred_projects'] ?? null),
            ':certifications' => $this->nullableString($payload['certifications'] ?? null),
            ':start_date' => $this->nullableString($payload['start_date'] ?? null),
            ':end_date' => $this->nullableString($payload['end_date'] ?? null),
        ]);

        $staffId = (int) $this->pdo->lastInsertId();
        $this->replaceSkills($staffId, $payload['skills'] ?? []);

        return [
            'id' => $staffId,
            'action' => 'created',
        ];
    }

    public function updateAvailability(int $staffId, string $availabilityStatus, ?float $currentFte = null): array
    {
        $availabilityStatus = strtolower(trim($availabilityStatus));
        if (!in_array($availabilityStatus, ['available', 'allocated', 'leaving'], true)) {
            throw new InvalidArgumentException('availability_status must be available, allocated, or leaving.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE staff
             SET availability_status = :availability_status,
                 current_fte = COALESCE(:current_fte, current_fte)
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $staffId,
            ':availability_status' => $availabilityStatus,
            ':current_fte' => $currentFte,
        ]);

        return [
            'id' => $staffId,
            'availability_status' => $availabilityStatus,
            'current_fte' => $currentFte,
            'action' => 'updated',
        ];
    }

    private function replaceSkills(int $staffId, mixed $skills): void
    {
        $deleteStmt = $this->pdo->prepare('DELETE FROM staff_skills WHERE staff_id = :staff_id');
        $deleteStmt->execute([':staff_id' => $staffId]);

        if (!is_array($skills)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO staff_skills (staff_id, skill_name) VALUES (:staff_id, :skill_name)'
        );

        foreach ($skills as $skill) {
            $name = trim((string) $skill);
            if ($name === '') {
                continue;
            }
            $insert->execute([
                ':staff_id' => $staffId,
                ':skill_name' => $name,
            ]);
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
