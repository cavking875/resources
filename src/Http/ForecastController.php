<?php

declare(strict_types=1);

namespace App\Http;

use App\Forecast\ForecastEngine;
use App\Forecast\ForecastPersistenceService;
use PDO;

final class ForecastController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forecast(array $payload): array
    {
        $engine = new ForecastEngine();

        return $engine->forecastMonthly(
            (array) ($payload['project'] ?? []),
            (array) ($payload['rules'] ?? []),
            (array) ($payload['phaseMultipliers'] ?? []),
            (array) ($payload['complexityMultipliers'] ?? [])
        );
    }

    public function persist(array $payload): array
    {
        $engine = new ForecastEngine();
        $forecastRows = $engine->forecastMonthly(
            (array) ($payload['project'] ?? []),
            (array) ($payload['rules'] ?? []),
            (array) ($payload['phaseMultipliers'] ?? []),
            (array) ($payload['complexityMultipliers'] ?? [])
        );

        $persistence = new ForecastPersistenceService($this->pdo);

        return $persistence->persist(
            (int) ($payload['project_id'] ?? 0),
            $forecastRows,
            (string) ($payload['source'] ?? 'rules')
        );
    }
}
