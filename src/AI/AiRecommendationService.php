<?php

declare(strict_types=1);

namespace App\AI;

use InvalidArgumentException;
use PDO;

final class AiRecommendationService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $projectId, array $payload): array
    {
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id must be a positive integer.');
        }

        $type = strtolower(trim((string) ($payload['recommendation_type'] ?? 'resource_plan')));
        $text = trim((string) ($payload['recommendation_text'] ?? ''));
        $rawJson = $payload['raw_json'] ?? null;
        $confidence = isset($payload['confidence_score']) ? (float) $payload['confidence_score'] : null;

        if ($text === '') {
            throw new InvalidArgumentException('recommendation_text is required.');
        }
        if ($confidence !== null && ($confidence < 0 || $confidence > 1)) {
            throw new InvalidArgumentException('confidence_score must be between 0 and 1.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_recommendations (
                project_id, recommendation_type, recommendation_text, raw_json, confidence_score
             ) VALUES (
                :project_id, :recommendation_type, :recommendation_text, :raw_json, :confidence_score
             )'
        );
        $stmt->execute([
            ':project_id' => $projectId,
            ':recommendation_type' => $type,
            ':recommendation_text' => $text,
            ':raw_json' => $rawJson === null ? null : json_encode($rawJson, JSON_THROW_ON_ERROR),
            ':confidence_score' => $confidence,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'action' => 'created',
        ];
    }

    public function listByProject(int $projectId, ?string $type = null): array
    {
        if ($projectId <= 0) {
            throw new InvalidArgumentException('project_id must be a positive integer.');
        }

        $params = [':project_id' => $projectId];
        $where = 'WHERE ar.project_id = :project_id';
        if ($type !== null && trim($type) !== '') {
            $where .= ' AND ar.recommendation_type = :recommendation_type';
            $params[':recommendation_type'] = trim($type);
        }

        $stmt = $this->pdo->prepare(
            "SELECT ar.id, ar.project_id, ar.recommendation_type, ar.recommendation_text,
                    ar.raw_json, ar.confidence_score, ar.created_at
             FROM ai_recommendations ar
             {$where}
             ORDER BY ar.id DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            if (is_string($row['raw_json'] ?? null) && $row['raw_json'] !== '') {
                $decoded = json_decode((string) $row['raw_json'], true);
                if (is_array($decoded)) {
                    $row['raw_json'] = $decoded;
                }
            }
        }

        return [
            'project_id' => $projectId,
            'rows' => $rows,
        ];
    }
}
