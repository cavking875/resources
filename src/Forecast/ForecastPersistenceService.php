<?php

declare(strict_types=1);

namespace App\Forecast;

use InvalidArgumentException;
use PDO;

final class ForecastPersistenceService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function persist(int $projectId, array $forecastRows, string $source = 'rules'): array
    {
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id must be a positive integer.');
        }

        if ($forecastRows === []) {
            throw new InvalidArgumentException('No forecast rows supplied for persistence.');
        }

        $roles = $this->roleIndex();
        $saved = 0;

        $this->pdo->beginTransaction();
        try {
            foreach ($forecastRows as $row) {
                $month = (string) ($row['month'] ?? '');
                if ($month === '') {
                    continue;
                }

                $monthDate = $month . '-01';
                $monthRoles = (array) ($row['roles'] ?? []);
                foreach ($monthRoles as $roleName => $values) {
                    $roleId = $roles[$roleName] ?? null;
                    if ($roleId === null) {
                        continue;
                    }

                    $requiredFte = (float) (($values['required_fte'] ?? 0));
                    $aiReason = isset($values['reason']) ? (string) $values['reason'] : null;

                    $stmt = $this->pdo->prepare(
                        'INSERT INTO resource_forecasts (project_id, role_id, month, required_fte, source, ai_reason)
                         VALUES (:project_id, :role_id, :month, :required_fte, :source, :ai_reason)
                         ON DUPLICATE KEY UPDATE
                            required_fte = VALUES(required_fte),
                            source = VALUES(source),
                            ai_reason = VALUES(ai_reason),
                            updated_at = CURRENT_TIMESTAMP'
                    );

                    $stmt->execute([
                        ':project_id' => $projectId,
                        ':role_id' => $roleId,
                        ':month' => $monthDate,
                        ':required_fte' => $requiredFte,
                        ':source' => $source,
                        ':ai_reason' => $aiReason,
                    ]);
                    $saved++;
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return [
            'project_id' => $projectId,
            'records_saved' => $saved,
            'source' => $source,
        ];
    }

    private function roleIndex(): array
    {
        $stmt = $this->pdo->query('SELECT id, role_name FROM resource_roles WHERE active = 1');
        $rows = $stmt->fetchAll();

        $index = [];
        foreach ($rows as $row) {
            $index[(string) $row['role_name']] = (int) $row['id'];
        }

        return $index;
    }
}
