<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('api_rate_limits');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->boolean('active')->default(true);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('role_name');
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->integer('user_id');
            $table->integer('role_id');
        });

        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('token_hash')->unique();
            $table->dateTime('expires_at');
        });

        Schema::create('api_rate_limits', function (Blueprint $table): void {
            $table->string('rate_key');
            $table->dateTime('window_start');
            $table->integer('attempts')->default(0);
            $table->unique(['rate_key', 'window_start']);
        });
    }

    public function test_login_returns_token_payload_for_valid_credentials(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@resourceplanner.local',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'active' => 1,
        ]);

        DB::table('roles')->insert([
            'id' => 1,
            'role_name' => 'Admin',
        ]);

        DB::table('user_roles')->insert([
            'user_id' => 1,
            'role_id' => 1,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@resourceplanner.local',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.expires_in_hours', 12)
            ->assertJsonPath('data.user.email', 'admin@resourceplanner.local')
            ->assertJsonPath('data.user.role', 'Admin');
    }

    public function test_login_rejects_invalid_json_body(): void
    {
        $response = $this->call(
            'POST',
            '/api/auth/login',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{not-valid-json'
        );

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid JSON request body.']);
    }

    public function test_logout_revokes_token(): void
    {
        $token = 'sample-token';

        DB::table('user_sessions')->insert([
            'user_id' => 1,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('data.revoked', true);
    }

    public function test_logout_requires_bearer_token(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(422)
            ->assertJson(['error' => 'Bearer token is required.']);
    }
}
