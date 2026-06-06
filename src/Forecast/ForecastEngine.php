<?php

declare(strict_types=1);

namespace App\Forecast;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use InvalidArgumentException;

final class ForecastEngine
{
    private const DEFAULT_PHASE_SEQUENCE = [
        ['phase' => 'tender', 'weight' => 0.05],
        ['phase' => 'design', 'weight' => 0.20],
        ['phase' => 'mobilisation', 'weight' => 0.10],
        ['phase' => 'construction', 'weight' => 0.55],
        ['phase' => 'commissioning', 'weight' => 0.08],
        ['phase' => 'closeout', 'weight' => 0.02],
    ];

    public function forecastMonthly(
        array $project,
        array $rules,
        array $phaseMultipliers,
        array $complexityMultipliers
    ): array {
        $this->validateProject($project);

        $projectValue = (float) ($project['project_value'] ?? 0.0);
        $complexity = strtolower((string) ($project['complexity_level'] ?? 'medium'));

        $effectiveRules = $rules !== [] ? $rules : $this->defaultRules();
        $effectivePhaseMultipliers = $phaseMultipliers !== [] ? $phaseMultipliers : $this->defaultPhaseMultipliers();
        $effectiveComplexity = $complexityMultipliers !== [] ? $complexityMultipliers : $this->defaultComplexityMultipliers();

        $baseRoleFte = $this->resolveBaseRoleFte($projectValue, $effectiveRules);
        $complexityMultiplier = (float) ($effectiveComplexity[$complexity] ?? 1.0);

        $months = $this->monthsBetween($project['start_date'], $project['end_date']);
        if (count($months) === 0) {
            throw new InvalidArgumentException('Project dates produced no forecast months.');
        }

        $phaseByMonth = $this->buildPhaseMap($months, $project['phase_distribution'] ?? self::DEFAULT_PHASE_SEQUENCE);

        $output = [];
        foreach ($months as $index => $month) {
            $phase = $phaseByMonth[$index]['phase'];
            $roles = [];

            foreach ($baseRoleFte as $role => $baseFte) {
                $phaseMultiplier = (float) ($effectivePhaseMultipliers[$phase][$role] ?? 1.0);
                $intensityMultiplier = $this->intensityMultiplier($role, $project);
                $required = round($baseFte * $phaseMultiplier * $complexityMultiplier * $intensityMultiplier, 2);
                $roles[$role] = [
                    'required_fte' => $required,
                    'inputs' => [
                        'base_fte' => $baseFte,
                        'phase_multiplier' => $phaseMultiplier,
                        'complexity_multiplier' => $complexityMultiplier,
                        'intensity_multiplier' => $intensityMultiplier,
                    ],
                ];
            }

            $output[] = [
                'month' => $month,
                'phase' => $phase,
                'roles' => $roles,
            ];
        }

        return $output;
    }

    private function validateProject(array $project): void
    {
        foreach (['project_value', 'start_date', 'end_date'] as $required) {
            if (!array_key_exists($required, $project)) {
                throw new InvalidArgumentException("Missing required project field: {$required}");
            }
        }

        $start = DateTimeImmutable::createFromFormat('Y-m-d', (string) $project['start_date']);
        $end = DateTimeImmutable::createFromFormat('Y-m-d', (string) $project['end_date']);
        if (!$start || !$end) {
            throw new InvalidArgumentException('start_date and end_date must use Y-m-d format.');
        }

        if ($start > $end) {
            throw new InvalidArgumentException('start_date must be before or equal to end_date.');
        }
    }

    private function monthsBetween(string $startDate, string $endDate): array
    {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        $cursor = new DateTimeImmutable($start->format('Y-m-01'));
        $endMonth = new DateTimeImmutable($end->format('Y-m-01'));

        $months = [];
        while ($cursor <= $endMonth) {
            $months[] = $cursor->format('Y-m');
            $cursor = $cursor->add(new DateInterval('P1M'));
        }

        return $months;
    }

    private function buildPhaseMap(array $months, array $phaseDistribution): array
    {
        $total = count($months);
        $mapped = [];
        $allocated = 0;

        foreach ($phaseDistribution as $i => $phaseRule) {
            $phaseName = strtolower((string) ($phaseRule['phase'] ?? 'construction'));
            $weight = (float) ($phaseRule['weight'] ?? 0.0);

            if ($i === count($phaseDistribution) - 1) {
                $slots = $total - $allocated;
            } else {
                $slots = (int) floor($total * $weight);
            }

            for ($j = 0; $j < $slots; $j++) {
                $mapped[] = ['phase' => $phaseName];
            }
            $allocated += $slots;
        }

        while (count($mapped) < $total) {
            $mapped[] = ['phase' => 'construction'];
        }

        return array_slice($mapped, 0, $total);
    }

    private function resolveBaseRoleFte(float $projectValue, array $rules): array
    {
        foreach ($rules as $band) {
            $min = (float) ($band['min'] ?? 0.0);
            $max = $band['max'] === null ? INF : (float) $band['max'];
            if ($projectValue >= $min && $projectValue < $max) {
                return $band['roles'];
            }
        }

        throw new InvalidArgumentException('No matching resource rule found for project value.');
    }

    private function intensityMultiplier(string $role, array $project): float
    {
        $roleLower = strtolower($role);

        $planningIntensity = strtolower((string) ($project['planning_intensity'] ?? 'medium'));
        $commercialIntensity = strtolower((string) ($project['commercial_intensity'] ?? 'medium'));
        $sitePresence = strtolower((string) ($project['site_presence_required'] ?? 'full-time'));

        $planningMap = ['low' => 0.9, 'medium' => 1.0, 'high' => 1.2];
        $commercialMap = ['low' => 0.9, 'medium' => 1.0, 'high' => 1.2];
        $siteMap = ['visiting' => 0.4, 'part-time' => 0.7, 'full-time' => 1.0];

        if (str_contains($roleLower, 'planner')) {
            return (float) ($planningMap[$planningIntensity] ?? 1.0);
        }

        if (
            str_contains($roleLower, 'commercial')
            || str_contains($roleLower, 'qs')
            || str_contains($roleLower, 'quantity surveyor')
        ) {
            return (float) ($commercialMap[$commercialIntensity] ?? 1.0);
        }

        if (
            str_contains($roleLower, 'site manager')
            || str_contains($roleLower, 'engineer')
            || str_contains($roleLower, 'foreman')
            || str_contains($roleLower, 'supervisor')
        ) {
            return (float) ($siteMap[$sitePresence] ?? 1.0);
        }

        return 1.0;
    }

    private function defaultRules(): array
    {
        return [
            ['min' => 0, 'max' => 500000, 'roles' => [
                'Project Planner' => 0.10,
                'Quantity Surveyor' => 0.10,
                'Site Manager' => 0.20,
                'Site Engineer' => 0.10,
                'Document Controller' => 0.05,
            ]],
            ['min' => 500000, 'max' => 2000000, 'roles' => [
                'Project Planner' => 0.25,
                'Quantity Surveyor' => 0.25,
                'Site Manager' => 0.50,
                'Site Engineer' => 0.25,
                'Document Controller' => 0.10,
            ]],
            ['min' => 2000000, 'max' => 5000000, 'roles' => [
                'Project Planner' => 0.50,
                'Quantity Surveyor' => 0.50,
                'Site Manager' => 1.00,
                'Site Engineer' => 0.50,
                'Document Controller' => 0.20,
            ]],
            ['min' => 5000000, 'max' => 10000000, 'roles' => [
                'Project Planner' => 1.00,
                'Quantity Surveyor' => 1.00,
                'Site Manager' => 1.50,
                'Site Engineer' => 1.00,
                'Document Controller' => 0.40,
            ]],
            ['min' => 10000000, 'max' => 25000000, 'roles' => [
                'Project Planner' => 1.50,
                'Quantity Surveyor' => 2.00,
                'Site Manager' => 2.50,
                'Site Engineer' => 2.00,
                'Document Controller' => 0.75,
            ]],
            ['min' => 25000000, 'max' => null, 'roles' => [
                'Project Planner' => 2.00,
                'Quantity Surveyor' => 3.00,
                'Site Manager' => 3.00,
                'Site Engineer' => 3.00,
                'Document Controller' => 1.00,
            ]],
        ];
    }

    private function defaultPhaseMultipliers(): array
    {
        return [
            'tender' => [
                'Project Planner' => 1.20,
                'Quantity Surveyor' => 1.00,
                'Site Manager' => 0.30,
                'Site Engineer' => 0.30,
                'Document Controller' => 0.60,
            ],
            'design' => [
                'Project Planner' => 1.00,
                'Quantity Surveyor' => 1.00,
                'Site Manager' => 0.50,
                'Site Engineer' => 0.80,
                'Document Controller' => 0.80,
            ],
            'mobilisation' => [
                'Project Planner' => 1.50,
                'Quantity Surveyor' => 1.00,
                'Site Manager' => 1.00,
                'Site Engineer' => 1.00,
                'Document Controller' => 1.00,
            ],
            'construction' => [
                'Project Planner' => 1.00,
                'Quantity Surveyor' => 1.20,
                'Site Manager' => 1.20,
                'Site Engineer' => 1.20,
                'Document Controller' => 1.00,
            ],
            'commissioning' => [
                'Project Planner' => 1.00,
                'Quantity Surveyor' => 1.00,
                'Site Manager' => 1.20,
                'Site Engineer' => 1.20,
                'Document Controller' => 1.00,
            ],
            'closeout' => [
                'Project Planner' => 0.50,
                'Quantity Surveyor' => 1.10,
                'Site Manager' => 0.40,
                'Site Engineer' => 0.40,
                'Document Controller' => 0.80,
            ],
        ];
    }

    private function defaultComplexityMultipliers(): array
    {
        return [
            'low' => 0.8,
            'medium' => 1.0,
            'high' => 1.25,
            'critical' => 1.5,
        ];
    }
}
