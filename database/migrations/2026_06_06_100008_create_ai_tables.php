<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('recommendation_type', 80);
            $table->text('recommendation_text');
            $table->json('raw_json')->nullable();
            $table->decimal('confidence_score', 4, 2)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['project_id', 'recommendation_type']);
            $table->foreign('project_id')->references('id')->on('projects');
        });

        Schema::create('ai_prompts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('prompt_type', 80);
            $table->text('system_prompt');
            $table->mediumText('user_prompt_template');
            $table->json('guardrails_json')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['prompt_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_recommendations');
    }
};
