<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_job_id')->nullable()->unique();
            $table->string('report_key');
            $table->string('collector');
            $table->string('collector_version')->nullable();
            $table->string('schema_version')->nullable();
            $table->string('status')->default('PENDING_SUBMISSION');
            $table->string('source')->default('manual');
            $table->json('parameters');
            $table->json('metadata')->nullable();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->unique();
            $table->char('request_fingerprint', 64);
            $table->string('request_id')->index();
            $table->unsignedInteger('progress_current')->nullable();
            $table->unsignedInteger('progress_total')->nullable();
            $table->string('progress_message')->nullable();
            $table->unsignedSmallInteger('submission_attempts')->default(0);
            $table->boolean('submission_retryable')->nullable();
            $table->unsignedSmallInteger('provider_attempts')->nullable();
            $table->unsignedSmallInteger('provider_max_attempts')->nullable();
            $table->unsignedBigInteger('result_count')->nullable();
            $table->string('result_checksum')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_submission_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('retry_of_job_id')->nullable()->constrained('automation_jobs')->nullOnDelete();
            $table->timestamps();

            $table->index(['report_key', 'status']);
            $table->index(['source', 'created_at']);
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_jobs');
    }
};
