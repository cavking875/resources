<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('role_name', 120)->unique();
            $table->string('department', 80);
            $table->decimal('default_cost_rate', 10, 2)->nullable();
            $table->decimal('default_charge_rate', 10, 2)->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('resource_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->decimal('min_project_value', 15, 2);
            $table->decimal('max_project_value', 15, 2)->nullable();
            $table->decimal('base_fte', 6, 2);
            $table->string('project_type', 120)->nullable();
            $table->string('delivery_model', 80)->nullable();
            $table->boolean('active')->default(true);
            $table->index(['min_project_value', 'max_project_value']);
            $table->foreign('role_id')->references('id')->on('resource_roles');
        });

        Schema::create('phase_multipliers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('phase_name', 50);
            $table->decimal('multiplier', 6, 2);
            $table->unique(['role_id', 'phase_name']);
            $table->foreign('role_id')->references('id')->on('resource_roles');
        });

        Schema::create('complexity_multipliers', function (Blueprint $table): void {
            $table->id();
            $table->string('complexity_level', 30)->unique();
            $table->decimal('multiplier', 6, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complexity_multipliers');
        Schema::dropIfExists('phase_multipliers');
        Schema::dropIfExists('resource_rules');
        Schema::dropIfExists('resource_roles');
    }
};
