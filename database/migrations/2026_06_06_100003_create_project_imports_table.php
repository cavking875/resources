<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name', 255);
            $table->char('file_hash', 64)->unique();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('import_status', 30);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->string('error_report_path', 255)->nullable();
            $table->json('mapping_json')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->index('import_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_imports');
    }
};
