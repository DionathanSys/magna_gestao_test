<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculos', function (Blueprint $table) {
            if (! Schema::hasColumn('veiculos', 'mapa_pneu_id')) {
                $table->foreignId('mapa_pneu_id')
                    ->nullable()
                    ->after('tipo_veiculo_id')
                    ->constrained('mapas_pneu')
                    ->nullOnDelete();
            }
        });

        Schema::table('pneu_posicao_veiculo', function (Blueprint $table) {
            if (! Schema::hasColumn('pneu_posicao_veiculo', 'mapa_pneu_posicao_id')) {
                $table->foreignId('mapa_pneu_posicao_id')
                    ->nullable()
                    ->after('veiculo_id')
                    ->constrained('mapa_pneu_posicoes')
                    ->nullOnDelete();

                $table->unique(['veiculo_id', 'mapa_pneu_posicao_id'], 'pneu_posicao_veiculo_mapa_posicao_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pneu_posicao_veiculo', function (Blueprint $table) {
            if (Schema::hasColumn('pneu_posicao_veiculo', 'mapa_pneu_posicao_id')) {
                $table->dropUnique('pneu_posicao_veiculo_mapa_posicao_unique');
                $table->dropConstrainedForeignId('mapa_pneu_posicao_id');
            }
        });

        Schema::table('veiculos', function (Blueprint $table) {
            if (Schema::hasColumn('veiculos', 'mapa_pneu_id')) {
                $table->dropConstrainedForeignId('mapa_pneu_id');
            }
        });
    }
};
