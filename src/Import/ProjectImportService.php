<?php

declare(strict_types=1);

namespace App\Import;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class ProjectImportService
{
    private CsvProjectValidator $validator;

    public function __construct(private PDO $pdo)
    {
        $this->validator = new CsvProjectValidator();
    }

    public function import(string $fileName, array $rows, mixed $uploadedBy = null): array
    {
        if ($rows === []) {
            throw new InvalidArgumentException('rows must contain at least one project row.');
        }

        $hash = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
        if ($this->isDuplicateFile($hash)) {
            throw new RuntimeException('This upload matches a previously imported file hash.');
        }

        $validation = $this->validator->validateRows($rows);
        $importId = $this->createImportRecord($fileName, $hash, $uploadedBy, 'processing', count($rows), 0);

        $inserted = 0;
        $failed = 0;
        $errors = [];

        $this->pdo->beginTransaction();
        try {
            foreach ($validation['rows'] as $rowResult) {
                if ($rowResult['status'] === 'error') {
                    $failed++;
                    $errors[] = [
                        'line' => $rowResult['line'],
                        'errors' => $rowResult['errors'],
                    ];
                    continue;
                }

                $clean = $rowResult['cleaned'];
                $this->insertProject($clean);
                $inserted++;
            }

            $status = $failed === 0 ? 'completed' : ($inserted > 0 ? 'partially_failed' : 'failed');
            $this->updateImportRecord($importId, $status, $inserted, $failed, $errors);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->updateImportRecord($importId, 'failed', $inserted, $failed + 1, [['error' => $e->getMessage()]]);
            throw new RuntimeException('Import failed: ' . $e->getMessage());
        }

        return [
            'import_id' => $importId,
            'file_hash' => $hash,
            'summary' => [
                'rows_received' => count($rows),
                'rows_inserted' => $inserted,
                'rows_failed' => $failed,
            ],
            'validation_summary' => $validation['summary'],
        ];
    }

    public function history(int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = min(200, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query('SELECT COUNT(*) AS total FROM project_imports');
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = $this->pdo->prepare(
            'SELECT pi.id, pi.file_name, pi.file_hash, pi.uploaded_by, u.name AS uploaded_by_name,
                    pi.import_status, pi.rows_imported, pi.rows_failed, pi.uploaded_at
             FROM project_imports pi
             LEFT JOIN users u ON u.id = pi.uploaded_by
             ORDER BY pi.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'rows' => $stmt->fetchAll(),
        ];
    }

    public function findImport(int $importId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pi.id, pi.file_name, pi.file_hash, pi.uploaded_by, u.name AS uploaded_by_name,
                    pi.import_status, pi.rows_imported, pi.rows_failed, pi.error_report_path,
                    pi.mapping_json, pi.uploaded_at
             FROM project_imports pi
             LEFT JOIN users u ON u.id = pi.uploaded_by
             WHERE pi.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $importId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $mapping = $row['mapping_json'] ?? null;
        if (is_string($mapping) && $mapping !== '') {
            $decoded = json_decode($mapping, true);
            $row['mapping_json'] = is_array($decoded) ? $decoded : $mapping;
        }

        return $row;
    }

    private function insertProject(array $clean): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (
                project_name, client_name, location, region, project_value,
                start_date, end_date, project_type, contract_type, delivery_model,
                complexity_level, risk_level
            ) VALUES (
                :project_name, :client_name, :location, :region, :project_value,
                :start_date, :end_date, :project_type, :contract_type, :delivery_model,
                :complexity_level, :risk_level
            )'
        );

        $stmt->execute([
            ':project_name' => $clean['project_name'] ?? '',
            ':client_name' => $clean['client'] ?? null,
            ':location' => $clean['location'] ?? null,
            ':region' => $clean['region'] ?? null,
            ':project_value' => $clean['project_value'] ?? 0,
            ':start_date' => $clean['start_date'] ?? null,
            ':end_date' => $clean['completion_date'] ?? null,
            ':project_type' => $clean['project_type'] ?? null,
            ':contract_type' => $clean['contract_type'] ?? null,
            ':delivery_model' => $clean['delivery_model'] ?? null,
            ':complexity_level' => $clean['complexity'] ?? 'medium',
            ':risk_level' => $clean['risk_level'] ?? 'medium',
        ]);
    }

    private function isDuplicateFile(string $hash): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM project_imports WHERE file_hash = :hash');
        $stmt->execute([':hash' => $hash]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0) > 0;
    }

    private function createImportRecord(
        string $fileName,
        string $hash,
        mixed $uploadedBy,
        string $status,
        int $rowsImported,
        int $rowsFailed
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO project_imports (
                file_name, file_hash, uploaded_by, import_status, rows_imported, rows_failed
            ) VALUES (
                :file_name, :file_hash, :uploaded_by, :import_status, :rows_imported, :rows_failed
            )'
        );

        $stmt->execute([
            ':file_name' => $fileName,
            ':file_hash' => $hash,
            ':uploaded_by' => is_numeric($uploadedBy) ? (int) $uploadedBy : null,
            ':import_status' => $status,
            ':rows_imported' => $rowsImported,
            ':rows_failed' => $rowsFailed,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function updateImportRecord(int $id, string $status, int $rowsImported, int $rowsFailed, array $errors): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE project_imports
             SET import_status = :status,
                 rows_imported = :rows_imported,
                 rows_failed = :rows_failed,
                 mapping_json = :mapping_json
             WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':rows_imported' => $rowsImported,
            ':rows_failed' => $rowsFailed,
            ':mapping_json' => json_encode(['errors' => $errors], JSON_THROW_ON_ERROR),
        ]);
    }
}
