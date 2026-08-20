<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manutencao_lancamentos', function (Blueprint $table) {
            $table->foreignId('resultado_periodo_id')
                ->nullable()
                ->after('veiculo_id')
                ->constrained('resultado_periodos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('manutencao_lancamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resultado_periodo_id');
        });
    }
};
