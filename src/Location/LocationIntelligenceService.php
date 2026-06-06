<?php

declare(strict_types=1);

namespace App\Location;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class LocationIntelligenceService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function projectMap(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, project_name, client_name, location, region, latitude, longitude,
                    project_stage, project_value, start_date, end_date
             FROM projects
             WHERE latitude IS NOT NULL AND longitude IS NOT NULL
             ORDER BY id DESC'
        );

        $rows = $stmt->fetchAll();

        return [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    public function staffMap(): array
    {
        $stmt = $this->pdo->query(
            'SELECT st.id, st.first_name, st.last_name, st.location, st.region, st.latitude, st.longitude,
                    st.availability_status, st.current_fte, st.max_fte, st.travel_radius_miles,
                    rr.role_name
             FROM staff st
             LEFT JOIN resource_roles rr ON rr.id = st.role_id
             WHERE st.active = 1
               AND st.latitude IS NOT NULL
               AND st.longitude IS NOT NULL
             ORDER BY st.id DESC'
        );

        $rows = $stmt->fetchAll();

        return [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    public function regionalHeatmap(?string $month = null): array
    {
        $monthDate = $this->normalizeMonth($month);

        $demandStmt = $this->pdo->prepare(
            'SELECT p.region, SUM(rf.required_fte) AS total_required
             FROM resource_forecasts rf
             INNER JOIN projects p ON p.id = rf.project_id
             WHERE rf.month = :month
             GROUP BY p.region'
        );
        $demandStmt->execute([':month' => $monthDate]);
        $demandRows = $demandStmt->fetchAll();

        $capacityStmt = $this->pdo->query(
            'SELECT region, SUM(max_fte) AS total_capacity
             FROM staff
             WHERE active = 1
             GROUP BY region'
        );
        $capacityRows = $capacityStmt->fetchAll();

        $demandByRegion = [];
        foreach ($demandRows as $row) {
            $region = (string) ($row['region'] ?? 'Unknown');
            $demandByRegion[$region] = (float) ($row['total_required'] ?? 0.0);
        }

        $capacityByRegion = [];
        foreach ($capacityRows as $row) {
            $region = (string) ($row['region'] ?? 'Unknown');
            $capacityByRegion[$region] = (float) ($row['total_capacity'] ?? 0.0);
        }

        $regions = array_unique(array_merge(array_keys($demandByRegion), array_keys($capacityByRegion)));
        sort($regions);

        $rows = [];
        foreach ($regions as $region) {
            $required = round((float) ($demandByRegion[$region] ?? 0.0), 2);
            $capacity = round((float) ($capacityByRegion[$region] ?? 0.0), 2);
            $gap = round($capacity - $required, 2);
            $pressure = $capacity > 0 ? round(($required / $capacity) * 100, 2) : null;

            $rows[] = [
                'region' => $region,
                'required_fte' => $required,
                'capacity_fte' => $capacity,
                'gap_fte' => $gap,
                'pressure_percent' => $pressure,
                'status' => $gap < 0 ? 'under' : ($gap > 0 ? 'over' : 'balanced'),
            ];
        }

        return [
            'month' => (new DateTimeImmutable($monthDate))->format('Y-m'),
            'rows' => $rows,
        ];
    }

    public function sharedResourceSuggestions(
        ?int $roleId = null,
        float $radiusMiles = 20.0,
        ?string $month = null
    ): array {
        if ($radiusMiles <= 0) {
            throw new InvalidArgumentException('radius_miles must be greater than zero.');
        }

        $monthDate = $this->normalizeMonth($month);
        $projectNeeds = $this->projectRoleNeeds($monthDate, $roleId);
        $staff = $this->availableStaff($roleId);

        $rows = [];
        foreach ($projectNeeds as $need) {
            if ((float) ($need['uncovered_fte'] ?? 0.0) <= 0) {
                continue;
            }

            $projectLat = $need['latitude'] !== null ? (float) $need['latitude'] : null;
            $projectLon = $need['longitude'] !== null ? (float) $need['longitude'] : null;
            if ($projectLat === null || $projectLon === null) {
                continue;
            }

            $candidates = [];
            foreach ($staff as $person) {
                $staffLat = $person['latitude'] !== null ? (float) $person['latitude'] : null;
                $staffLon = $person['longitude'] !== null ? (float) $person['longitude'] : null;
                if ($staffLat === null || $staffLon === null) {
                    continue;
                }

                $distance = $this->distanceMiles($projectLat, $projectLon, $staffLat, $staffLon);
                if ($distance > $radiusMiles) {
                    continue;
                }

                $availableFte = max(0.0, (float) $person['max_fte'] - (float) $person['current_fte']);
                if ($availableFte <= 0) {
                    continue;
                }

                $candidates[] = [
                    'staff_id' => (int) $person['id'],
                    'staff_name' => trim((string) $person['first_name'] . ' ' . $person['last_name']),
                    'role_name' => (string) ($person['role_name'] ?? ''),
                    'available_fte' => round($availableFte, 2),
                    'distance_miles' => round($distance, 2),
                    'region' => (string) ($person['region'] ?? ''),
                ];
            }

            usort(
                $candidates,
                static fn (array $a, array $b): int => $a['distance_miles'] <=> $b['distance_miles']
            );

            if ($candidates === []) {
                continue;
            }

            $rows[] = [
                'project_id' => (int) $need['project_id'],
                'project_name' => (string) ($need['project_name'] ?? ''),
                'region' => (string) ($need['region'] ?? ''),
                'role_name' => (string) ($need['role_name'] ?? ''),
                'required_fte' => round((float) ($need['required_fte'] ?? 0.0), 2),
                'allocated_fte' => round((float) ($need['allocated_fte'] ?? 0.0), 2),
                'uncovered_fte' => round((float) ($need['uncovered_fte'] ?? 0.0), 2),
                'candidate_count' => count($candidates),
                'candidates' => array_slice($candidates, 0, 5),
            ];
        }

        return [
            'month' => (new DateTimeImmutable($monthDate))->format('Y-m'),
            'radius_miles' => $radiusMiles,
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

    private function projectRoleNeeds(string $monthDate, ?int $roleId): array
    {
        $sql =
            'SELECT
                p.id AS project_id,
                p.project_name,
                p.region,
                p.latitude,
                p.longitude,
                rr.id AS role_id,
                rr.role_name,
                SUM(rf.required_fte) AS required_fte,
                COALESCE(alloc.allocated_fte, 0) AS allocated_fte,
                SUM(rf.required_fte) - COALESCE(alloc.allocated_fte, 0) AS uncovered_fte
             FROM resource_forecasts rf
             INNER JOIN projects p ON p.id = rf.project_id
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             LEFT JOIN (
                SELECT ra.project_id, ra.role_id, SUM(ra.allocation_fte) AS allocated_fte
                FROM resource_allocations ra
                WHERE ra.start_date <= LAST_DAY(:month)
                  AND ra.end_date >= :month
                GROUP BY ra.project_id, ra.role_id
             ) alloc
               ON alloc.project_id = rf.project_id AND alloc.role_id = rf.role_id
             WHERE rf.month = :month';

        $params = [':month' => $monthDate];

        if ($roleId !== null && $roleId > 0) {
            $sql .= ' AND rf.role_id = :role_id';
            $params[':role_id'] = $roleId;
        }

        $sql .= '
             GROUP BY p.id, p.project_name, p.region, p.latitude, p.longitude, rr.id, rr.role_name, alloc.allocated_fte
             ORDER BY uncovered_fte DESC, p.project_name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function availableStaff(?int $roleId): array
    {
        $sql =
            'SELECT st.id, st.first_name, st.last_name, st.region, st.latitude, st.longitude,
                    st.max_fte, st.current_fte, st.availability_status,
                    rr.id AS role_id, rr.role_name
             FROM staff st
             INNER JOIN resource_roles rr ON rr.id = st.role_id
             WHERE st.active = 1
               AND st.availability_status IN ("available", "allocated")';

        $params = [];
        if ($roleId !== null && $roleId > 0) {
            $sql .= ' AND st.role_id = :role_id';
            $params[':role_id'] = $roleId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function distanceMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMiles = 3958.8;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMiles * $c;
    }
}
