<?php

namespace Tests\Feature;

class ApiStaffAllocationsTest extends ApiTestCase
{
    public function test_list_staff_requires_auth(): void
    {
        $response = $this->getJson('/api/staff');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_create_staff_requires_auth(): void
    {
        $response = $this->postJson('/api/staff', ['first_name' => 'John']);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_warnings_returns_empty_for_no_conflicts(): void
    {
        $payload = [
            'staff' => [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'max_fte' => 1.0],
            ],
            'allocations' => [
                ['staff_id' => 1, 'allocation_fte' => 0.5, 'start_date' => '2026-01-01', 'end_date' => '2026-06-30'],
                ['staff_id' => 1, 'allocation_fte' => 0.5, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31'],
            ],
        ];

        $response = $this->postJson('/api/allocations/warnings', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['warning_count', 'warnings']]);

        $this->assertEquals(0, $response->json('data.warning_count'));
    }

    public function test_warnings_detects_overallocation(): void
    {
        $payload = [
            'staff' => [
                ['id' => 1, 'first_name' => 'Jane', 'last_name' => 'Smith', 'max_fte' => 1.0],
            ],
            'allocations' => [
                ['staff_id' => 1, 'allocation_fte' => 0.8, 'start_date' => '2026-01-01', 'end_date' => '2026-09-30'],
                ['staff_id' => 1, 'allocation_fte' => 0.8, 'start_date' => '2026-06-01', 'end_date' => '2026-12-31'],
            ],
        ];

        $response = $this->postJson('/api/allocations/warnings', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['warning_count', 'warnings']]);

        $this->assertGreaterThan(0, $response->json('data.warning_count'));
    }
}
