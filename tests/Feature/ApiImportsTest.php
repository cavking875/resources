<?php

namespace Tests\Feature;

class ApiImportsTest extends ApiTestCase
{
    public function test_validate_rows_returns_validation_summary(): void
    {
        $payload = [
            'rows' => [
                [
                    'Project Name' => 'Test Project',
                    'Location' => 'London',
                    'Region' => 'South East',
                    'Project Value' => '5000000',
                    'Start Date' => '01/01/2026',
                    'Completion Date' => '31/12/2026',
                    'Project Type' => 'New Build',
                    'Contract Type' => 'JCT',
                    'Delivery Model' => 'Traditional',
                ],
            ],
        ];

        $response = $this->postJson('/api/imports/projects/validate', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['summary' => ['total_rows', 'valid_rows', 'rows_with_errors'], 'rows']]);
    }

    public function test_validate_rows_rejects_invalid_json(): void
    {
        $response = $this->call(
            'POST',
            '/api/imports/projects/validate',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{bad-json'
        );

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid JSON request body.']);
    }

    public function test_import_projects_requires_auth(): void
    {
        $response = $this->postJson('/api/imports/projects', ['rows' => []]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_import_history_requires_auth(): void
    {
        $response = $this->getJson('/api/imports/projects/history');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }
}
