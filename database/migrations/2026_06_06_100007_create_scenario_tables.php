<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table): void {
            $table->id();
            $table->string('scenario_name', 120);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('base_case')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('scenario_projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scenario_id');
            $table->unsignedBigInteger('project_id');
            $table->decimal('adjusted_value', 15, 2)->nullable();
            $table->date('adjusted_start_date')->nullable();
            $table->date('adjusted_end_date')->nullable();
            $table->boolean('included')->default(true);
            $table->foreign('scenario_id')->references('id')->on('scenarios');
            $table->foreign('project_id')->references('id')->on('projects');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_projects');
        Schema::dropIfExists('scenarios');
    }
};
