<?php

declare(strict_types=1);

namespace App\Import;

use DateTimeImmutable;

final class CsvProjectValidator
{
    private const REQUIRED_COLUMNS = [
        'Project Name',
        'Location',
        'Region',
        'Project Value',
        'Start Date',
        'Completion Date',
        'Project Type',
        'Contract Type',
        'Delivery Model',
    ];

    public function validateRows(array $rows): array
    {
        $summary = [
            'total_rows' => count($rows),
            'valid_rows' => 0,
            'rows_with_warnings' => 0,
            'rows_with_errors' => 0,
        ];

        $results = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $issues = ['warnings' => [], 'errors' => []];

            if (!is_array($row)) {
                $issues['errors'][] = 'Row is not a valid object.';
                $summary['rows_with_errors']++;
                $results[] = $this->formatResult($line, [], $issues);
                continue;
            }

            $cleaned = $this->cleanRow($row, $issues);
            $duplicateKey = strtolower((string) ($cleaned['project_name'] ?? ''))
                . '|' . ($cleaned['start_date'] ?? '')
                . '|' . strtolower((string) ($cleaned['location'] ?? ''));

            if ($duplicateKey !== '||') {
                if (isset($seen[$duplicateKey])) {
                    $issues['warnings'][] = 'Potential duplicate project row detected in this upload batch.';
                }
                $seen[$duplicateKey] = true;
            }

            if ($issues['errors'] !== []) {
                $summary['rows_with_errors']++;
            } elseif ($issues['warnings'] !== []) {
                $summary['rows_with_warnings']++;
                $summary['valid_rows']++;
            } else {
                $summary['valid_rows']++;
            }

            $results[] = $this->formatResult($line, $cleaned, $issues);
        }

        return [
            'summary' => $summary,
            'rows' => $results,
        ];
    }

    private function cleanRow(array $row, array &$issues): array
    {
        $clean = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value === '') {
                $issues['errors'][] = "Missing required field: {$column}";
            }
        }

        $clean['project_name'] = trim((string) ($row['Project Name'] ?? ''));
        $clean['client'] = trim((string) ($row['Client'] ?? ''));
        $clean['location'] = trim((string) ($row['Location'] ?? ''));
        $clean['region'] = trim((string) ($row['Region'] ?? ''));
        $clean['project_type'] = trim((string) ($row['Project Type'] ?? ''));
        $clean['contract_type'] = trim((string) ($row['Contract Type'] ?? ''));
        $clean['delivery_model'] = trim((string) ($row['Delivery Model'] ?? ''));
        $clean['complexity'] = strtolower(trim((string) ($row['Complexity'] ?? 'medium')));
        $clean['risk_level'] = strtolower(trim((string) ($row['Risk Level'] ?? 'medium')));

        $clean['project_value'] = $this->cleanCurrency((string) ($row['Project Value'] ?? ''), $issues);
        $clean['start_date'] = $this->normalizeDate((string) ($row['Start Date'] ?? ''), 'Start Date', $issues);
        $clean['completion_date'] = $this->normalizeDate((string) ($row['Completion Date'] ?? ''), 'Completion Date', $issues);

        if ($clean['start_date'] !== null && $clean['completion_date'] !== null) {
            if ($clean['start_date'] > $clean['completion_date']) {
                $issues['errors'][] = 'Start Date must be on or before Completion Date.';
            }
        }

        if (!in_array($clean['complexity'], ['low', 'medium', 'high', 'critical'], true)) {
            $issues['warnings'][] = 'Complexity should be low, medium, high, or critical.';
        }

        return $clean;
    }

    private function cleanCurrency(string $value, array &$issues): ?float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', $value) ?? '';
        if ($normalized === '') {
            $issues['errors'][] = 'Project Value is missing or invalid.';
            return null;
        }

        if (!is_numeric($normalized)) {
            $issues['errors'][] = 'Project Value must be numeric after currency cleanup.';
            return null;
        }

        $result = (float) $normalized;
        if ($result <= 0) {
            $issues['errors'][] = 'Project Value must be greater than zero.';
            return null;
        }

        return $result;
    }

    private function normalizeDate(string $value, string $field, array &$issues): ?string
    {
        $value = trim($value);
        if ($value === '') {
            $issues['errors'][] = "{$field} is required.";
            return null;
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format('Y-m-d');
            }
        }

        $issues['errors'][] = "{$field} date format not recognized.";
        return null;
    }

    private function formatResult(int $line, array $cleaned, array $issues): array
    {
        return [
            'line' => $line,
            'status' => $issues['errors'] === [] ? ($issues['warnings'] === [] ? 'valid' : 'warning') : 'error',
            'cleaned' => $cleaned,
            'warnings' => $issues['warnings'],
            'errors' => $issues['errors'],
        ];
    }
}
