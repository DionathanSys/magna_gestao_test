<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('provider_job_id')->index();
            $table->string('client_id');
            $table->string('event_type');
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_status')->default('RECEIVED');
            $table->text('processing_error')->nullable();
            $table->string('request_id')->nullable()->index();
            $table->timestamps();

            $table->index(['provider_job_id', 'event_type']);
            $table->index(['processing_status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_events');
    }
};
