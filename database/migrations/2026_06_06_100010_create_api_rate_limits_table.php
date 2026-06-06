<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_rate_limits', function (Blueprint $table): void {
            $table->id();
            $table->string('rate_key', 190);
            $table->dateTime('window_start');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['rate_key', 'window_start']);
            $table->index('rate_key');
            $table->index('window_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
    }
};
