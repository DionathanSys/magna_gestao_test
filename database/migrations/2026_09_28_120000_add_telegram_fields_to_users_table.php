<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('telegram_chat_id', 64)
                ->nullable()
                ->unique()
                ->after('email');
            $table->boolean('telegram_reminders_enabled')
                ->default(false)
                ->after('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_telegram_chat_id_unique');
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_reminders_enabled',
            ]);
        });
    }
};
