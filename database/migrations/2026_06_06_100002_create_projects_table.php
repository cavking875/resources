<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('project_name', 255);
            $table->string('client_name', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
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
            $table->string('planning_intensity', 30)->default('medium');
            $table->string('commercial_intensity', 30)->default('medium');
            $table->string('site_presence_required', 30)->default('full-time');
            $table->string('framework_name', 80)->nullable();
            $table->timestamps();
            $table->index('region');
            $table->index(['start_date', 'end_date']);
            $table->index('project_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
