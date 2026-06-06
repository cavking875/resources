<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected string $token = '';
    protected array $headers = [];

    /**
     * Create auth-related tables and seed a valid admin user + session.
     * Drop existing tables first so tests are idempotent.
     */
    protected function setUpAuthTables(): void
    {
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

        $this->token = 'test-bearer-token';
        $tokenHash = hash('sha256', $this->token);

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@resourceplanner.local',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'active' => 1,
        ]);
        DB::table('roles')->insert(['id' => 1, 'role_name' => 'Admin']);
        DB::table('user_roles')->insert(['user_id' => 1, 'role_id' => 1]);
        DB::table('user_sessions')->insert([
            'user_id' => 1,
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+12 hours')),
        ]);

        $this->headers = ['Authorization' => 'Bearer ' . $this->token];
    }
}
