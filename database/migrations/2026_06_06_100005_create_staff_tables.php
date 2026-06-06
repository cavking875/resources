<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('employment_type', 30);
            $table->decimal('salary_cost', 10, 2)->nullable();
            $table->decimal('day_rate', 10, 2)->nullable();
            $table->decimal('charge_out_rate', 10, 2)->nullable();
            $table->string('availability_status', 30)->default('available');
            $table->decimal('max_fte', 4, 2)->default(1.00);
            $table->decimal('current_fte', 4, 2)->default(0.00);
            $table->unsignedInteger('travel_radius_miles')->nullable();
            $table->string('preferred_projects', 255)->nullable();
            $table->string('certifications', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->index('region');
            $table->index('availability_status');
        });

        Schema::create('staff_skills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('skill_name', 120);
            $table->index('staff_id');
            $table->foreign('staff_id')->references('id')->on('staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_skills');
        Schema::dropIfExists('staff');
    }
};
