<?php

declare(strict_types=1);

namespace App\AI;

use InvalidArgumentException;
use PDO;

final class AiPromptService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listPrompts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, prompt_type, system_prompt, user_prompt_template, guardrails_json, active, created_at, updated_at
             FROM ai_prompts
             ORDER BY active DESC, id DESC'
        );
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            if (is_string($row['guardrails_json'] ?? null) && $row['guardrails_json'] !== '') {
                $decoded = json_decode((string) $row['guardrails_json'], true);
                if (is_array($decoded)) {
                    $row['guardrails_json'] = $decoded;
                }
            }
        }

        return $rows;
    }

    public function activeByType(string $promptType): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, prompt_type, system_prompt, user_prompt_template, guardrails_json, active
             FROM ai_prompts
             WHERE prompt_type = :prompt_type AND active = 1
             LIMIT 1'
        );
        $stmt->execute([':prompt_type' => trim($promptType)]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        if (is_string($row['guardrails_json'] ?? null) && $row['guardrails_json'] !== '') {
            $decoded = json_decode((string) $row['guardrails_json'], true);
            if (is_array($decoded)) {
                $row['guardrails_json'] = $decoded;
            }
        }

        return $row;
    }

    public function upsert(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $promptType = strtolower(trim((string) ($payload['prompt_type'] ?? '')));
        $systemPrompt = trim((string) ($payload['system_prompt'] ?? ''));
        $userPromptTemplate = trim((string) ($payload['user_prompt_template'] ?? ''));
        $guardrails = $payload['guardrails_json'] ?? null;
        $active = isset($payload['active']) ? (int) ((bool) $payload['active']) : 1;
        $id = isset($payload['id']) ? (int) $payload['id'] : null;

        if ($name === '' || $promptType === '' || $systemPrompt === '' || $userPromptTemplate === '') {
            throw new InvalidArgumentException('name, prompt_type, system_prompt, and user_prompt_template are required.');
        }

        $guardrailsJson = $guardrails === null ? null : json_encode($guardrails, JSON_THROW_ON_ERROR);

        if ($id !== null && $id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE ai_prompts
                 SET name = :name,
                     prompt_type = :prompt_type,
                     system_prompt = :system_prompt,
                     user_prompt_template = :user_prompt_template,
                     guardrails_json = :guardrails_json,
                     active = :active
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':prompt_type' => $promptType,
                ':system_prompt' => $systemPrompt,
                ':user_prompt_template' => $userPromptTemplate,
                ':guardrails_json' => $guardrailsJson,
                ':active' => $active,
            ]);

            if ($active === 1) {
                $this->deactivateOthers($promptType, $id);
            }

            return ['id' => $id, 'action' => 'updated'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_prompts (
                name, prompt_type, system_prompt, user_prompt_template, guardrails_json, active
             ) VALUES (
                :name, :prompt_type, :system_prompt, :user_prompt_template, :guardrails_json, :active
             )'
        );
        $stmt->execute([
            ':name' => $name,
            ':prompt_type' => $promptType,
            ':system_prompt' => $systemPrompt,
            ':user_prompt_template' => $userPromptTemplate,
            ':guardrails_json' => $guardrailsJson,
            ':active' => $active,
        ]);

        $newId = (int) $this->pdo->lastInsertId();
        if ($active === 1) {
            $this->deactivateOthers($promptType, $newId);
        }

        return ['id' => $newId, 'action' => 'created'];
    }

    private function deactivateOthers(string $promptType, int $activeId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ai_prompts
             SET active = 0
             WHERE prompt_type = :prompt_type AND id <> :active_id'
        );
        $stmt->execute([
            ':prompt_type' => $promptType,
            ':active_id' => $activeId,
        ]);
    }
}
