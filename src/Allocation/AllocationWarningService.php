<?php

declare(strict_types=1);

namespace App\Allocation;

use DateTimeImmutable;

final class AllocationWarningService
{
    public function evaluate(array $staffList, array $allocations): array
    {
        $staffIndex = [];
        foreach ($staffList as $staff) {
            if (!is_array($staff) || !isset($staff['id'])) {
                continue;
            }
            $staffIndex[(string) $staff['id']] = $staff;
        }

        $warnings = [];
        $grouped = [];

        foreach ($allocations as $allocation) {
            if (!is_array($allocation)) {
                continue;
            }
            $staffId = (string) ($allocation['staff_id'] ?? '');
            if ($staffId === '') {
                continue;
            }
            $grouped[$staffId][] = $allocation;
        }

        foreach ($grouped as $staffId => $records) {
            $maxFte = (float) ($staffIndex[$staffId]['max_fte'] ?? 1.0);
            $name = trim((string) (($staffIndex[$staffId]['first_name'] ?? '') . ' ' . ($staffIndex[$staffId]['last_name'] ?? '')));
            $name = $name !== '' ? $name : "staff {$staffId}";

            for ($i = 0; $i < count($records); $i++) {
                for ($j = $i + 1; $j < count($records); $j++) {
                    $a = $records[$i];
                    $b = $records[$j];

                    if ($this->overlaps($a['start_date'] ?? '', $a['end_date'] ?? '', $b['start_date'] ?? '', $b['end_date'] ?? '')) {
                        $combined = (float) ($a['allocation_fte'] ?? 0) + (float) ($b['allocation_fte'] ?? 0);
                        if ($combined > $maxFte) {
                            $warnings[] = [
                                'type' => 'over_allocation',
                                'staff_id' => $staffId,
                                'staff_name' => $name,
                                'message' => "{$name} is allocated {$combined} FTE across overlapping assignments (max {$maxFte}).",
                                'allocations' => [
                                    $this->compactAllocation($a),
                                    $this->compactAllocation($b),
                                ],
                            ];
                        }
                    }
                }
            }

            $total = 0.0;
            foreach ($records as $record) {
                $total += (float) ($record['allocation_fte'] ?? 0);
            }

            if ($total < 0.25) {
                $warnings[] = [
                    'type' => 'under_allocation',
                    'staff_id' => $staffId,
                    'staff_name' => $name,
                    'message' => "{$name} has low committed workload ({$total} FTE total in provided window).",
                ];
            }
        }

        return [
            'warning_count' => count($warnings),
            'warnings' => $warnings,
        ];
    }

    private function overlaps(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        try {
            $aStartDate = new DateTimeImmutable($aStart);
            $aEndDate = new DateTimeImmutable($aEnd);
            $bStartDate = new DateTimeImmutable($bStart);
            $bEndDate = new DateTimeImmutable($bEnd);
        } catch (\Exception) {
            return false;
        }

        return $aStartDate <= $bEndDate && $bStartDate <= $aEndDate;
    }

    private function compactAllocation(array $allocation): array
    {
        return [
            'project_id' => $allocation['project_id'] ?? null,
            'role_id' => $allocation['role_id'] ?? null,
            'allocation_fte' => (float) ($allocation['allocation_fte'] ?? 0),
            'start_date' => $allocation['start_date'] ?? null,
            'end_date' => $allocation['end_date'] ?? null,
            'status' => $allocation['status'] ?? null,
        ];
    }
}
