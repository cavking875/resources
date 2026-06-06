<?php

declare(strict_types=1);

namespace App\Report;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

final class CsvExportService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function roleGapCsv(?string $month = null): string
    {
        $monthDate = $this->normalizeMonth($month);

        $stmt = $this->pdo->prepare(
            'SELECT rr.role_name,
                    SUM(rf.required_fte) AS required_fte,
                    COALESCE(cap.total_available, 0) AS current_fte,
                    COALESCE(cap.total_available, 0) - SUM(rf.required_fte) AS gap_fte
             FROM resource_forecasts rf
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             LEFT JOIN (
                SELECT role_id, SUM(max_fte) AS total_available
                FROM staff
                WHERE active = 1
                GROUP BY role_id
             ) cap ON cap.role_id = rr.id
             WHERE rf.month = :month
             GROUP BY rr.role_name, cap.total_available
             ORDER BY rr.role_name ASC'
        );
        $stmt->execute([':month' => $monthDate]);
        $rows = $stmt->fetchAll();

        return $this->toCsv(
            ['month', 'role', 'required_fte', 'current_fte', 'gap_fte'],
            array_map(
                static fn (array $r): array => [
                    (new DateTimeImmutable($monthDate))->format('Y-m'),
                    (string) $r['role_name'],
                    (string) round((float) $r['required_fte'], 2),
                    (string) round((float) $r['current_fte'], 2),
                    (string) round((float) $r['gap_fte'], 2),
                ],
                $rows
            )
        );
    }

    public function monthlyDemandCsv(?string $startMonth = null, int $months = 12): string
    {
        if ($months < 1 || $months > 24) {
            throw new InvalidArgumentException('months must be between 1 and 24.');
        }

        $start = new DateTimeImmutable($this->normalizeMonth($startMonth));
        $end = $start->modify('+' . ($months - 1) . ' months');

        $stmt = $this->pdo->prepare(
            'SELECT DATE_FORMAT(rf.month, "%Y-%m") AS month,
                    rr.role_name,
                    SUM(rf.required_fte) AS required_fte
             FROM resource_forecasts rf
             INNER JOIN resource_roles rr ON rr.id = rf.role_id
             WHERE rf.month BETWEEN :start_month AND :end_month
             GROUP BY DATE_FORMAT(rf.month, "%Y-%m"), rr.role_name
             ORDER BY month ASC, rr.role_name ASC'
        );
        $stmt->execute([
            ':start_month' => $start->format('Y-m-01'),
            ':end_month' => $end->format('Y-m-01'),
        ]);
        $rows = $stmt->fetchAll();

        return $this->toCsv(
            ['month', 'role', 'required_fte'],
            array_map(
                static fn (array $r): array => [
                    (string) $r['month'],
                    (string) $r['role_name'],
                    (string) round((float) $r['required_fte'], 2),
                ],
                $rows
            )
        );
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

    private function toCsv(array $header, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new InvalidArgumentException('Failed to open temporary stream for CSV export.');
        }

        fputcsv($stream, $header);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }
}
