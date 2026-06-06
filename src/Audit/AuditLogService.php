<?php

declare(strict_types=1);

namespace App\Audit;

use JsonException;
use PDO;

final class AuditLogService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function log(
        ?int $userId,
        string $entityType,
        ?int $entityId,
        string $action,
        mixed $oldValues = null,
        mixed $newValues = null,
        ?string $reason = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, entity_type, entity_id, action, old_values, new_values, reason)
             VALUES (:user_id, :entity_type, :entity_id, :action, :old_values, :new_values, :reason)'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':action' => $action,
            ':old_values' => $this->toJson($oldValues),
            ':new_values' => $this->toJson($newValues),
            ':reason' => $reason,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function toJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
