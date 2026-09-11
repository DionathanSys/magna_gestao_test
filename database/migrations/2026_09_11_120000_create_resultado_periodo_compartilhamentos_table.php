<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultado_periodo_compartilhamentos', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->json('resultado_periodo_ids');
            $table->string('destinatario_nome');
            $table->string('destinatario_email')->nullable();
            $table->foreignId('criado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado_periodo_compartilhamentos');
    }
};
