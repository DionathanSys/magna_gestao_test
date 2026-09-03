<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->foreignId('cte_email_request_id')
                ->nullable()
                ->constrained('cte_email_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cte_email_request_id');
        });
    }
};
