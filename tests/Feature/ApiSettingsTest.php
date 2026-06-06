<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApiSettingsTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthTables();

        Schema::dropIfExists('resource_rules');
        Schema::dropIfExists('resource_roles');

        Schema::create('resource_roles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('role_name', 120)->unique();
            $table->string('department', 80);
            $table->decimal('default_cost_rate', 10, 2)->nullable();
            $table->decimal('default_charge_rate', 10, 2)->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('resource_rules', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('role_id');
            $table->decimal('min_project_value', 15, 2);
            $table->decimal('max_project_value', 15, 2)->nullable();
            $table->decimal('base_fte', 6, 2);
            $table->string('project_type', 120)->nullable();
            $table->string('delivery_model', 80)->nullable();
            $table->boolean('active')->default(true);
        });
    }

    public function test_resource_roles_requires_auth(): void
    {
        $response = $this->getJson('/api/settings/resource-roles');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_audit_logs_requires_auth(): void
    {
        $response = $this->getJson('/api/audit-logs');

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Bearer token is required.']);
    }

    public function test_resource_rules_returns_list(): void
    {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/settings/resource-rules');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertIsArray($response->json('data'));
    }
}
