<?php

declare(strict_types=1);

namespace App\Http;

use App\Allocation\AllocationService;
use App\Allocation\AllocationWarningService;
use App\Audit\AuditLogService;
use App\Staff\StaffRegisterService;
use PDO;

final class StaffAllocationsController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function warnings(array $payload): array
    {
        $service = new AllocationWarningService();

        return $service->evaluate(
            (array) ($payload['staff'] ?? []),
            (array) ($payload['allocations'] ?? [])
        );
    }

    public function listStaff(array $query): array
    {
        $service = new StaffRegisterService($this->pdo);

        return $service->list([
            'region' => $query['region'] ?? null,
            'availability_status' => $query['availability_status'] ?? null,
            'role_id' => $query['role_id'] ?? null,
        ]);
    }

    public function findStaff(int $staffId): ?array
    {
        $service = new StaffRegisterService($this->pdo);

        return $service->find($staffId);
    }

    public function createStaff(array $payload): array
    {
        $service = new StaffRegisterService($this->pdo);

        return $service->create($payload);
    }

    public function updateStaffAvailability(int $staffId, array $payload): array
    {
        $service = new StaffRegisterService($this->pdo);

        return $service->updateAvailability(
            $staffId,
            (string) ($payload['availability_status'] ?? ''),
            isset($payload['current_fte']) ? (float) $payload['current_fte'] : null
        );
    }

    public function createAllocation(array $payload, int $userId): array
    {
        $service = new AllocationService($this->pdo);
        $data = $service->create($payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'resource_allocation',
            (int) ($data['id'] ?? 0),
            'create',
            null,
            $payload,
            'Allocation created'
        );

        return $data;
    }

    public function updateAllocation(int $allocationId, array $payload, int $userId): array
    {
        $service = new AllocationService($this->pdo);
        $before = $service->find($allocationId);
        $data = $service->update($allocationId, $payload);
        $after = $service->find($allocationId);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'resource_allocation',
            $allocationId,
            'update',
            $before,
            $after,
            'Allocation updated'
        );

        return $data;
    }

    public function deleteAllocation(int $allocationId, int $userId): array
    {
        $service = new AllocationService($this->pdo);
        $before = $service->find($allocationId);
        $data = $service->delete($allocationId);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'resource_allocation',
            $allocationId,
            'delete',
            $before,
            null,
            'Allocation deleted'
        );

        return $data;
    }
}
