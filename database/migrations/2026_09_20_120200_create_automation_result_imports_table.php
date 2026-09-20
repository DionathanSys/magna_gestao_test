<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_result_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_job_id')->constrained('automation_jobs')->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->text('cursor')->nullable();
            $table->text('next_cursor')->nullable();
            $table->string('checksum')->nullable();
            $table->string('status')->default('PROCESSING');
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_ignored')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_job_id', 'page_number']);
            $table->index(['automation_job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_result_imports');
    }
};
