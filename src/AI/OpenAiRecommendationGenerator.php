<?php

declare(strict_types=1);

namespace App\AI;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class OpenAiRecommendationGenerator
{
    public function __construct(
        private PDO $pdo,
        private AiPromptService $promptService,
        private AiRecommendationService $recommendationService
    ) {
    }

    public function generateAndStore(int $projectId, string $promptType = 'resource_plan'): array
    {
        $project = $this->project($projectId);
        if ($project === null) {
            throw new InvalidArgumentException('Project not found.');
        }

        $prompt = $this->promptService->activeByType($promptType);
        if ($prompt === null) {
            throw new RuntimeException("No active AI prompt found for type: {$promptType}");
        }

        $systemPrompt = (string) $prompt['system_prompt'];
        $userPrompt = $this->applyTemplate((string) $prompt['user_prompt_template'], $project);

        $rawText = $this->callOpenAi($systemPrompt, $userPrompt, is_array($prompt['guardrails_json'] ?? null) ? $prompt['guardrails_json'] : []);
        $json = $this->extractJson($rawText);

        $text = trim((string) ($json['summary'] ?? 'AI resource recommendation generated.'));
        if ($text === '') {
            $text = 'AI resource recommendation generated.';
        }

        $confidence = isset($json['confidence_score']) ? (float) $json['confidence_score'] : null;

        $saved = $this->recommendationService->create($projectId, [
            'recommendation_type' => $promptType,
            'recommendation_text' => $text,
            'confidence_score' => $confidence,
            'raw_json' => $json,
        ]);

        return [
            'project_id' => $projectId,
            'prompt_type' => $promptType,
            'recommendation_id' => (int) ($saved['id'] ?? 0),
            'confidence_score' => $confidence,
            'raw_json' => $json,
        ];
    }

    private function project(int $projectId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, project_name, client_name, location, region, project_value,
                    project_type, contract_type, start_date, end_date, project_stage,
                    delivery_model, complexity_level, risk_level, planning_intensity,
                    commercial_intensity, site_presence_required
             FROM projects
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $projectId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function applyTemplate(string $template, array $project): string
    {
        $map = [
            '{{project_name}}' => (string) ($project['project_name'] ?? ''),
            '{{client_name}}' => (string) ($project['client_name'] ?? ''),
            '{{location}}' => (string) ($project['location'] ?? ''),
            '{{region}}' => (string) ($project['region'] ?? ''),
            '{{project_value}}' => (string) ($project['project_value'] ?? ''),
            '{{project_type}}' => (string) ($project['project_type'] ?? ''),
            '{{contract_type}}' => (string) ($project['contract_type'] ?? ''),
            '{{start_date}}' => (string) ($project['start_date'] ?? ''),
            '{{end_date}}' => (string) ($project['end_date'] ?? ''),
            '{{project_stage}}' => (string) ($project['project_stage'] ?? ''),
            '{{delivery_model}}' => (string) ($project['delivery_model'] ?? ''),
            '{{complexity_level}}' => (string) ($project['complexity_level'] ?? ''),
            '{{risk_level}}' => (string) ($project['risk_level'] ?? ''),
            '{{planning_intensity}}' => (string) ($project['planning_intensity'] ?? ''),
            '{{commercial_intensity}}' => (string) ($project['commercial_intensity'] ?? ''),
            '{{site_presence_required}}' => (string) ($project['site_presence_required'] ?? ''),
        ];

        return strtr($template, $map);
    }

    private function callOpenAi(string $systemPrompt, string $userPrompt, array $guardrails): string
    {
        $apiKey = getenv('OPENAI_API_KEY') ?: '';
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini';
        $temperature = isset($guardrails['temperature']) ? (float) $guardrails['temperature'] : 0.2;
        $maxTokens = isset($guardrails['max_tokens']) ? (int) $guardrails['max_tokens'] : 1200;

        $payload = [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize OpenAI request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('OpenAI request failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('OpenAI returned HTTP ' . $status . ': ' . $response);
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI response did not include text content.');
        }

        return $content;
    }

    private function extractJson(string $text): array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('AI response did not contain valid JSON payload.');
        }

        $jsonText = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($jsonText, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Failed to parse JSON from AI response.');
        }

        return $decoded;
    }
}
