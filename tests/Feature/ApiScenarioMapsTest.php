<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApiScenarioMapsTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthTables();

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('scenarios');

        Schema::create('scenarios', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('scenario_name', 120);
            $table->integer('created_by')->nullable();
            $table->boolean('base_case')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('entity_type', 80);
            $table->integer('entity_id')->nullable();
            $table->string('action', 40);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_scenarios_requires_auth(): void
    {
        $response = $this->getJson('/api/scenarios');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_maps_projects_requires_auth(): void
    {
        $response = $this->getJson('/api/maps/projects');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_create_scenario_requires_valid_payload(): void
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/scenarios', ['scenario_name' => 'Test Scenario']);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'action']]);

        $this->assertEquals('created', $response->json('data.action'));
    }
}
