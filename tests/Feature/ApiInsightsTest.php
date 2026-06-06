<?php

namespace Tests\Feature;

class ApiInsightsTest extends ApiTestCase
{
    public function test_role_gap_requires_auth(): void
    {
        $response = $this->getJson('/api/dashboards/role-gap');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_monthly_demand_requires_auth(): void
    {
        $response = $this->getJson('/api/dashboards/monthly-demand');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_financial_summary_requires_auth(): void
    {
        $response = $this->getJson('/api/dashboards/financial-summary');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }
}
