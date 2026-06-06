<?php

declare(strict_types=1);

namespace App\Http;

use App\Audit\AuditLogService;
use App\Location\LocationIntelligenceService;
use App\Scenario\ScenarioService;
use PDO;

final class ScenarioMapsController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function scenarios(): array
    {
        $service = new ScenarioService($this->pdo);

        return $service->list();
    }

    public function createScenario(array $payload, int $userId): array
    {
        $service = new ScenarioService($this->pdo);
        $data = $service->create(
            (string) ($payload['scenario_name'] ?? ''),
            $userId,
            isset($payload['base_case']) ? (bool) $payload['base_case'] : false
        );

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'scenario',
            (int) ($data['id'] ?? 0),
            'create',
            null,
            ['scenario_name' => $payload['scenario_name'] ?? null],
            'Scenario created'
        );

        return $data;
    }

    public function findScenario(int $scenarioId): ?array
    {
        $service = new ScenarioService($this->pdo);

        return $service->find($scenarioId);
    }

    public function upsertScenarioProject(int $scenarioId, array $payload, int $userId): array
    {
        $service = new ScenarioService($this->pdo);
        $data = $service->upsertProjectAdjustment($scenarioId, $payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'scenario_project',
            isset($data['id']) ? (int) $data['id'] : null,
            (string) ($data['action'] ?? 'upsert'),
            null,
            [
                'scenario_id' => $scenarioId,
                'project_id' => $payload['project_id'] ?? null,
                'adjusted_value' => $payload['adjusted_value'] ?? null,
                'included' => $payload['included'] ?? true,
            ],
            'Scenario project adjustment upserted'
        );

        return $data;
    }

    public function scenarioAnalysis(int $scenarioId, ?string $month): array
    {
        $service = new ScenarioService($this->pdo);

        return $service->analysis($scenarioId, $month);
    }

    public function projectMap(): array
    {
        $service = new LocationIntelligenceService($this->pdo);

        return $service->projectMap();
    }

    public function staffMap(): array
    {
        $service = new LocationIntelligenceService($this->pdo);

        return $service->staffMap();
    }

    public function heatmap(?string $month): array
    {
        $service = new LocationIntelligenceService($this->pdo);

        return $service->regionalHeatmap($month);
    }

    public function sharedResourceSuggestions(?int $roleId, float $radiusMiles, ?string $month): array
    {
        $service = new LocationIntelligenceService($this->pdo);

        return $service->sharedResourceSuggestions($roleId, $radiusMiles, $month);
    }
}
