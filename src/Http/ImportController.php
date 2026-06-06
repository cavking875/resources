<?php

declare(strict_types=1);

namespace App\Http;

use App\Audit\AuditLogService;
use App\Import\CsvProjectValidator;
use App\Import\ProjectImportService;
use PDO;

final class ImportController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function validateRows(array $payload): array
    {
        $validator = new CsvProjectValidator();

        return $validator->validateRows((array) ($payload['rows'] ?? []));
    }

    public function import(array $payload, int $userId): array
    {
        $service = new ProjectImportService($this->pdo);
        $result = $service->import(
            (string) ($payload['file_name'] ?? 'upload.csv'),
            (array) ($payload['rows'] ?? []),
            $userId
        );

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'project_import',
            (int) ($result['import_id'] ?? 0),
            'create',
            null,
            [
                'file_name' => (string) ($payload['file_name'] ?? 'upload.csv'),
                'rows_received' => (int) (($result['summary']['rows_received'] ?? 0)),
                'rows_inserted' => (int) (($result['summary']['rows_inserted'] ?? 0)),
                'rows_failed' => (int) (($result['summary']['rows_failed'] ?? 0)),
            ],
            'Project CSV import run'
        );

        return $result;
    }

    public function history(int $page, int $perPage): array
    {
        $service = new ProjectImportService($this->pdo);

        return $service->history($page, $perPage);
    }

    public function findImport(int $importId): ?array
    {
        $service = new ProjectImportService($this->pdo);

        return $service->findImport($importId);
    }
}
