<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_alert_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_alert_id')->constrained('telegram_alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('chat_id', 64);
            $table->string('status', 30)->default('pending');
            $table->string('telegram_message_id')->nullable();
            $table->string('telegram_document_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('message_sent_at')->nullable();
            $table->timestamp('document_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['telegram_alert_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_alert_recipients');
    }
};
