<?php

declare(strict_types=1);

namespace App\Report;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class AiSummaryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function boardSummary(?string $startMonth = null, int $months = 12): array
    {
        if ($months < 1 || $months > 24) {
            throw new InvalidArgumentException('months must be between 1 and 24.');
        }

        $start = $this->normalizeMonth($startMonth);
        $end = $start->add(new DateInterval('P' . ($months - 1) . 'M'));

        $demand = $this->demandByRole($start, $end);
        $capacity = $this->capacityByRole();
        $gapRows = $this->buildGaps($demand, $capacity);

        $pressure = $this->highestPressureMonth($start, $months);

        $underRoles = array_values(array_filter($gapRows, static fn (array $r): bool => $r['gap_fte'] < 0));
        usort($underRoles, static fn (array $a, array $b): int => $a['gap_fte'] <=> $b['gap_fte']);
        $topShortages = array_slice($underRoles, 0, 3);

        $headline = $this->buildHeadline($gapRows, $pressure, $months, $start);
        $hiringRecommendation = $this->buildHiringRecommendation($topShortages);
        $riskSummary = $this->buildRiskSummary($topShortages, $pressure);

        return [
            'period' => [
                'start_month' => $start->format('Y-m'),
                'end_month' => $end->format('Y-m'),
                'months' => $months,
            ],
            'role_gaps' => $gapRows,
            'peak_pressure' => $pressure,
            'board_summary' => $headline,
            'hiring_recommendation' => $hiringRecommendation,
            'risk_summary' => $riskSummary,
        ];
    }

    private function normalizeMonth(?string $month): DateTimeImmutable
    {
        if ($month === null || trim($month) === '') {
            return new DateTimeImmutable('first day of this month');
        }

        $month = trim($month);
        $accepted = DateTimeImmutable::createFromFormat('Y-m', $month);
        if ($accepted instanceof DateTimeImmutable) {
            return new DateTimeImmutable($accepted->format('Y-m-01'));
        }

        $accepted = DateTimeImmutable::createFromFormat('Y-m-d', $month);
        if ($accepted instanceof DateTimeImmutable) {
            return new DateTimeImmutable($accepted->format('Y-m-01'));
        }

        throw new InvalidArgumentException('start_month must be Y-m or Y-m-d format.');
    }

    private function demandByRole(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rr.role_name, AVG(rf.required_fte) AS avg_required
             FROM resource_forecasts rf
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             WHERE rf.month BETWEEN :start_month AND :end_month
             GROUP BY rr.role_name'
        );
        $stmt->execute([
            ':start_month' => $start->format('Y-m-01'),
            ':end_month' => $end->format('Y-m-01'),
        ]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role_name']] = round((float) $row['avg_required'], 2);
        }

        return $result;
    }

    private function capacityByRole(): array
    {
        $stmt = $this->pdo->query(
            'SELECT rr.role_name, SUM(st.max_fte) AS total_capacity
             FROM staff st
             INNER JOIN resource_roles rr ON rr.id = st.role_id
             WHERE st.active = 1
             GROUP BY rr.role_name'
        );
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role_name']] = round((float) $row['total_capacity'], 2);
        }

        return $result;
    }

    private function buildGaps(array $demand, array $capacity): array
    {
        $roles = array_unique(array_merge(array_keys($demand), array_keys($capacity)));
        sort($roles);

        $rows = [];
        foreach ($roles as $role) {
            $required = (float) ($demand[$role] ?? 0.0);
            $available = (float) ($capacity[$role] ?? 0.0);
            $gap = round($available - $required, 2);

            $rows[] = [
                'role' => $role,
                'required_fte' => round($required, 2),
                'current_fte' => round($available, 2),
                'gap_fte' => $gap,
                'status' => $gap < 0 ? 'under' : ($gap > 0 ? 'over' : 'balanced'),
            ];
        }

        return $rows;
    }

    private function highestPressureMonth(DateTimeImmutable $start, int $months): array
    {
        $end = $start->add(new DateInterval('P' . ($months - 1) . 'M'));

        $stmt = $this->pdo->prepare(
            'SELECT rf.month, SUM(rf.required_fte) AS total_required
             FROM resource_forecasts rf
             WHERE rf.month BETWEEN :start_month AND :end_month
             GROUP BY rf.month
             ORDER BY total_required DESC, rf.month ASC
             LIMIT 1'
        );
        $stmt->execute([
            ':start_month' => $start->format('Y-m-01'),
            ':end_month' => $end->format('Y-m-01'),
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'month' => $start->format('Y-m'),
                'total_required_fte' => 0.0,
            ];
        }

        return [
            'month' => (new DateTimeImmutable((string) $row['month']))->format('Y-m'),
            'total_required_fte' => round((float) $row['total_required'], 2),
        ];
    }

    private function buildHeadline(array $gapRows, array $pressure, int $months, DateTimeImmutable $start): string
    {
        $parts = [];
        foreach ($gapRows as $row) {
            if ($row['required_fte'] <= 0) {
                continue;
            }
            $parts[] = sprintf('%s %.2f', $row['role'], (float) $row['required_fte']);
        }

        $topLine = $parts === [] ? 'No forecasted demand available yet.' : implode(', ', array_slice($parts, 0, 4));

        return sprintf(
            'Over the %d-month period starting %s, average role demand is %s. The highest pressure period is %s with %.2f FTE total required.',
            $months,
            $start->format('Y-m'),
            $topLine,
            (string) $pressure['month'],
            (float) $pressure['total_required_fte']
        );
    }

    private function buildHiringRecommendation(array $topShortages): string
    {
        if ($topShortages === []) {
            return 'Current capacity appears aligned with forecast demand. Use contractors only for short-lived peaks.';
        }

        $lines = [];
        foreach ($topShortages as $row) {
            $needed = abs((float) $row['gap_fte']);
            $lines[] = sprintf('Recruit or contract %.2f FTE for %s.', $needed, (string) $row['role']);
        }

        return implode(' ', $lines);
    }

    private function buildRiskSummary(array $topShortages, array $pressure): string
    {
        if ($topShortages === []) {
            return sprintf('Resource risk is moderate. Peak month is %s but no critical role shortfalls are detected.', (string) $pressure['month']);
        }

        $roles = array_map(static fn (array $r): string => (string) $r['role'], $topShortages);
        return sprintf(
            'Resource risk is highest around %s due to shortages in %s.',
            (string) $pressure['month'],
            implode(', ', $roles)
        );
    }
}
