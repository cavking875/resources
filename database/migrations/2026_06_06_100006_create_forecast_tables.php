<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_forecasts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('role_id');
            $table->date('month');
            $table->decimal('required_fte', 8, 2);
            $table->string('source', 50)->default('rules');
            $table->text('ai_reason')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'role_id', 'month']);
            $table->index('month');
            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('role_id')->references('id')->on('resource_roles');
        });

        Schema::create('resource_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('role_id');
            $table->decimal('allocation_fte', 5, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['staff_id', 'start_date', 'end_date']);
            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('staff_id')->references('id')->on('staff');
            $table->foreign('role_id')->references('id')->on('resource_roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_allocations');
        Schema::dropIfExists('resource_forecasts');
    }
};
