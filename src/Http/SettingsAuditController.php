<?php

declare(strict_types=1);

namespace App\Http;

use App\AI\AiPromptService;
use App\Audit\AuditLogService;
use App\Audit\AuditQueryService;
use App\Settings\SettingsService;
use PDO;

final class SettingsAuditController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function auditLogs(array $query): array
    {
        $service = new AuditQueryService($this->pdo);

        return $service->list([
            'page' => $query['page'] ?? 1,
            'per_page' => $query['per_page'] ?? 25,
            'entity_type' => $query['entity_type'] ?? null,
            'action' => $query['action'] ?? null,
            'user_id' => $query['user_id'] ?? null,
            'from_date' => $query['from_date'] ?? null,
            'to_date' => $query['to_date'] ?? null,
        ]);
    }

    public function resourceRoles(): array
    {
        $service = new SettingsService($this->pdo);

        return $service->resourceRoles();
    }

    public function aiPrompts(): array
    {
        $service = new AiPromptService($this->pdo);

        return $service->listPrompts();
    }

    public function upsertAiPrompt(array $payload, int $userId): array
    {
        $service = new AiPromptService($this->pdo);
        $data = $service->upsert($payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'ai_prompt',
            isset($data['id']) ? (int) $data['id'] : null,
            (string) ($data['action'] ?? 'upsert'),
            null,
            [
                'prompt_type' => $payload['prompt_type'] ?? null,
                'name' => $payload['name'] ?? null,
                'active' => $payload['active'] ?? 1,
            ],
            'AI prompt template upserted'
        );

        return $data;
    }

    public function resourceRules(): array
    {
        $service = new SettingsService($this->pdo);

        return $service->resourceRules();
    }

    public function phaseMultipliers(): array
    {
        $service = new SettingsService($this->pdo);

        return $service->phaseMultipliers();
    }

    public function complexityMultipliers(): array
    {
        $service = new SettingsService($this->pdo);

        return $service->complexityMultipliers();
    }

    public function upsertResourceRule(array $payload, int $userId): array
    {
        $service = new SettingsService($this->pdo);
        $data = $service->upsertResourceRule($payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'resource_rule',
            isset($data['id']) ? (int) $data['id'] : null,
            (string) ($data['action'] ?? 'upsert'),
            null,
            $payload,
            'Resource rule upserted'
        );

        return $data;
    }

    public function upsertPhaseMultiplier(array $payload, int $userId): array
    {
        $service = new SettingsService($this->pdo);
        $data = $service->upsertPhaseMultiplier($payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'phase_multiplier',
            null,
            'upsert',
            null,
            $payload,
            'Phase multiplier upserted'
        );

        return $data;
    }

    public function upsertComplexityMultiplier(array $payload, int $userId): array
    {
        $service = new SettingsService($this->pdo);
        $data = $service->upsertComplexityMultiplier($payload);

        $audit = new AuditLogService($this->pdo);
        $audit->log(
            $userId,
            'complexity_multiplier',
            null,
            'upsert',
            null,
            $payload,
            'Complexity multiplier upserted'
        );

        return $data;
    }
}
