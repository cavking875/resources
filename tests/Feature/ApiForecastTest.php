<?php

namespace Tests\Feature;

class ApiForecastTest extends ApiTestCase
{
    public function test_forecast_returns_monthly_fte_breakdown(): void
    {
        $payload = [
            'project' => [
                'project_value' => 5000000,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'complexity_level' => 'medium',
            ],
            'rules' => [],
            'phaseMultipliers' => [],
            'complexityMultipliers' => [],
        ];

        $response = $this->postJson('/api/forecast', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('month', $data[0]);
        $this->assertArrayHasKey('roles', $data[0]);
    }

    public function test_forecast_rejects_invalid_json(): void
    {
        $response = $this->call(
            'POST',
            '/api/forecast',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{not-valid-json'
        );

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid JSON request body.']);
    }

    public function test_forecast_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/forecast', ['project' => []]);

        $response->assertStatus(422);
    }
}
