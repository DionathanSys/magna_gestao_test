<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cte_email_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viagem_id')->nullable()->constrained('viagens')->nullOnDelete();
            $table->foreignId('integrado_id')->nullable()->constrained('integrados')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('documento_transporte', 50)->index();
            $table->string('tipo_documento_solicitado', 50)->nullable();
            $table->string('status', 30)->default('pending_send')->index();
            $table->string('sent_subject')->nullable();
            $table->string('sent_to')->nullable();
            $table->string('sent_reply_to')->nullable();
            $table->text('sent_cc')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_response_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('cte_email_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cte_email_request_id')->constrained('cte_email_requests')->cascadeOnDelete();
            $table->foreignId('incoming_email_id')->nullable()->constrained('incoming_emails')->nullOnDelete();
            $table->string('direction', 20)->index();
            $table->string('message_id')->nullable();
            $table->string('from_email')->nullable();
            $table->string('subject')->nullable();
            $table->string('matched_by', 50)->nullable();
            $table->string('status', 30)->default('stored')->index();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['cte_email_request_id', 'incoming_email_id'], 'cte_email_request_incoming_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cte_email_request_messages');
        Schema::dropIfExists('cte_email_requests');
    }
};
