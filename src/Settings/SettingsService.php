<?php

declare(strict_types=1);

namespace App\Settings;

use InvalidArgumentException;
use PDO;

final class SettingsService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function resourceRoles(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, role_name, department, default_cost_rate, default_charge_rate, active
             FROM resource_roles
             ORDER BY department ASC, role_name ASC'
        );

        return $stmt->fetchAll();
    }

    public function resourceRules(): array
    {
        $stmt = $this->pdo->query(
            'SELECT rr.id, rr.role_id, r.role_name, rr.min_project_value, rr.max_project_value,
                    rr.base_fte, rr.project_type, rr.delivery_model, rr.active
             FROM resource_rules rr
             INNER JOIN resource_roles r ON r.id = rr.role_id
             ORDER BY r.role_name ASC, rr.min_project_value ASC'
        );

        return $stmt->fetchAll();
    }

    public function phaseMultipliers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT pm.id, pm.role_id, rr.role_name, pm.phase_name, pm.multiplier
             FROM phase_multipliers pm
             INNER JOIN resource_roles rr ON rr.id = pm.role_id
             ORDER BY rr.role_name ASC, pm.phase_name ASC'
        );

        return $stmt->fetchAll();
    }

    public function complexityMultipliers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, complexity_level, multiplier
             FROM complexity_multipliers
             ORDER BY FIELD(complexity_level, "low", "medium", "high", "critical"), id ASC'
        );

        return $stmt->fetchAll();
    }

    public function upsertResourceRule(array $payload): array
    {
        $roleId = (int) ($payload['role_id'] ?? 0);
        $min = (float) ($payload['min_project_value'] ?? 0);
        $maxRaw = $payload['max_project_value'] ?? null;
        $max = $maxRaw === null || $maxRaw === '' ? null : (float) $maxRaw;
        $baseFte = (float) ($payload['base_fte'] ?? -1);
        $projectType = $this->nullableString($payload['project_type'] ?? null);
        $deliveryModel = $this->nullableString($payload['delivery_model'] ?? null);
        $active = isset($payload['active']) ? (int) ((bool) $payload['active']) : 1;
        $id = isset($payload['id']) ? (int) $payload['id'] : null;

        if ($roleId <= 0) {
            throw new InvalidArgumentException('role_id must be a positive integer.');
        }
        if ($baseFte < 0) {
            throw new InvalidArgumentException('base_fte must be 0 or greater.');
        }
        if ($max !== null && $max <= $min) {
            throw new InvalidArgumentException('max_project_value must be greater than min_project_value.');
        }

        if ($id !== null && $id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE resource_rules
                 SET role_id = :role_id,
                     min_project_value = :min_project_value,
                     max_project_value = :max_project_value,
                     base_fte = :base_fte,
                     project_type = :project_type,
                     delivery_model = :delivery_model,
                     active = :active
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id' => $id,
                ':role_id' => $roleId,
                ':min_project_value' => $min,
                ':max_project_value' => $max,
                ':base_fte' => $baseFte,
                ':project_type' => $projectType,
                ':delivery_model' => $deliveryModel,
                ':active' => $active,
            ]);

            return ['id' => $id, 'action' => 'updated'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO resource_rules (
                role_id, min_project_value, max_project_value, base_fte,
                project_type, delivery_model, active
             ) VALUES (
                :role_id, :min_project_value, :max_project_value, :base_fte,
                :project_type, :delivery_model, :active
             )'
        );
        $stmt->execute([
            ':role_id' => $roleId,
            ':min_project_value' => $min,
            ':max_project_value' => $max,
            ':base_fte' => $baseFte,
            ':project_type' => $projectType,
            ':delivery_model' => $deliveryModel,
            ':active' => $active,
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'action' => 'created'];
    }

    public function upsertPhaseMultiplier(array $payload): array
    {
        $roleId = (int) ($payload['role_id'] ?? 0);
        $phaseName = strtolower(trim((string) ($payload['phase_name'] ?? '')));
        $multiplier = (float) ($payload['multiplier'] ?? 0);

        if ($roleId <= 0) {
            throw new InvalidArgumentException('role_id must be a positive integer.');
        }
        if ($phaseName === '') {
            throw new InvalidArgumentException('phase_name is required.');
        }
        if ($multiplier <= 0) {
            throw new InvalidArgumentException('multiplier must be greater than 0.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO phase_multipliers (role_id, phase_name, multiplier)
             VALUES (:role_id, :phase_name, :multiplier)
             ON DUPLICATE KEY UPDATE multiplier = VALUES(multiplier)'
        );
        $stmt->execute([
            ':role_id' => $roleId,
            ':phase_name' => $phaseName,
            ':multiplier' => $multiplier,
        ]);

        return [
            'role_id' => $roleId,
            'phase_name' => $phaseName,
            'multiplier' => $multiplier,
            'action' => 'upserted',
        ];
    }

    public function upsertComplexityMultiplier(array $payload): array
    {
        $level = strtolower(trim((string) ($payload['complexity_level'] ?? '')));
        $multiplier = (float) ($payload['multiplier'] ?? 0);

        if (!in_array($level, ['low', 'medium', 'high', 'critical'], true)) {
            throw new InvalidArgumentException('complexity_level must be low, medium, high, or critical.');
        }
        if ($multiplier <= 0) {
            throw new InvalidArgumentException('multiplier must be greater than 0.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO complexity_multipliers (complexity_level, multiplier)
             VALUES (:complexity_level, :multiplier)
             ON DUPLICATE KEY UPDATE multiplier = VALUES(multiplier)'
        );
        $stmt->execute([
            ':complexity_level' => $level,
            ':multiplier' => $multiplier,
        ]);

        return [
            'complexity_level' => $level,
            'multiplier' => $multiplier,
            'action' => 'upserted',
        ];
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
