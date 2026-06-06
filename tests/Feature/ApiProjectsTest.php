<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiProjectsTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthTables();

        Schema::dropIfExists('projects');
        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('project_name', 255);
            $table->string('client_name', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('region', 120)->nullable();
            $table->decimal('project_value', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('project_stage', 80)->nullable();
            $table->string('project_type', 120)->nullable();
            $table->string('contract_type', 120)->nullable();
            $table->string('delivery_model', 80)->nullable();
            $table->string('complexity_level', 30)->default('medium');
            $table->string('risk_level', 30)->default('medium');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_list_projects_returns_empty_without_auth(): void
    {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_list_projects_returns_paginated_list(): void
    {
        DB::table('projects')->insert([
            'project_name' => 'Alpha Project',
            'client_name' => 'Acme Corp',
            'location' => 'London',
            'region' => 'South East',
            'project_value' => 5000000.00,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'complexity_level' => 'medium',
            'risk_level' => 'medium',
            'created_at' => now(),
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['page', 'per_page', 'total', 'rows']]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    public function test_find_project_returns_404_for_unknown(): void
    {
        $response = $this->withHeaders($this->headers)->getJson('/api/projects/99999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Project not found.']);
    }

    public function test_project_allocations_requires_auth(): void
    {
        $response = $this->getJson('/api/projects/1/allocations');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }
}
