<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('justificativas_dispersao_viagens', function (Blueprint $table) {
            $table->foreignId('comentario_id')
                ->nullable()
                ->after('viagem_id')
                ->constrained('comentarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('justificativas_dispersao_viagens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comentario_id');
        });
    }
};
