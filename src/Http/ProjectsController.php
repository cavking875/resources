<?php

declare(strict_types=1);

namespace App\Http;

use App\AI\AiPromptService;
use App\AI\AiRecommendationService;
use App\AI\OpenAiRecommendationGenerator;
use App\Allocation\AllocationService;
use App\Audit\AuditLogService;
use App\Dashboard\ProjectGapService;
use App\Dashboard\ProjectMonthlyDemandService;
use App\Finance\FinancialModelService;
use App\Project\ProjectRegisterService;
use PDO;

final class ProjectsController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(array $query): array
    {
        $service = new ProjectRegisterService($this->pdo);

        return $service->list([
            'page' => $query['page'] ?? 1,
            'per_page' => $query['per_page'] ?? 25,
            'region' => $query['region'] ?? null,
            'project_stage' => $query['project_stage'] ?? null,
            'search' => $query['search'] ?? null,
        ]);
    }

    public function find(int $projectId): ?array
    {
        $service = new ProjectRegisterService($this->pdo);

        return $service->find($projectId);
    }

    public function allocations(int $projectId): array
    {
        $service = new AllocationService($this->pdo);

        return $service->listByProject($projectId);
    }

    public function monthlyDemand(int $projectId, ?string $startMonth, int $months): array
    {
        $service = new ProjectMonthlyDemandService($this->pdo);

        return $service->forProject($projectId, $startMonth, $months);
    }

    public function financials(int $projectId, float $targetPercent): array
    {
        $service = new FinancialModelService($this->pdo);

        return $service->projectFinancials($projectId, $targetPercent);
    }

    public function gap(int $projectId, ?string $month): array
    {
        $service = new ProjectGapService($this->pdo);

        return $service->byProject($projectId, $month);
    }

    public function aiRecommendations(int $projectId, ?string $type): array
    {
        $service = new AiRecommendationService($this->pdo);

        return $service->listByProject($projectId, $type);
    }

    public function createAiRecommendation(int $projectId, array $payload, int $userId): array
    {
        $service = new AiRecommendationService($this->pdo);
        $data = $service->create($projectId, $payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'ai_recommendation',
            (int) ($data['id'] ?? 0),
            'create',
            null,
            [
                'project_id' => $projectId,
                'recommendation_type' => $payload['recommendation_type'] ?? 'resource_plan',
            ],
            'AI recommendation stored'
        );

        return $data;
    }

    public function generateAiRecommendation(int $projectId, string $promptType, int $userId): array
    {
        $promptService = new AiPromptService($this->pdo);
        $recommendationService = new AiRecommendationService($this->pdo);
        $generator = new OpenAiRecommendationGenerator($this->pdo, $promptService, $recommendationService);

        $data = $generator->generateAndStore($projectId, $promptType);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'ai_recommendation',
            (int) ($data['recommendation_id'] ?? 0),
            'generate',
            null,
            [
                'project_id' => $projectId,
                'prompt_type' => $promptType,
                'confidence_score' => $data['confidence_score'] ?? null,
            ],
            'AI recommendation generated from OpenAI'
        );

        return $data;
    }
}
