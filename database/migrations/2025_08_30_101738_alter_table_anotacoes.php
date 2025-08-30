<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('anotacoes_veiculo', function (Blueprint $table) {
            $table->dropForeign([
                'servico_id',
            ]);
            $table->dropForeign([
                'item_ordem_servico_id',
            ]);
            $table->dropForeign([
                'tecnico_manutencao_id'
            ]);
            $table->dropColumn([
                'servico_id',
                'item_ordem_servico_id',
                'tecnico_manutencao_id',
                'tipo',
                'status',
                'prioridade',
            ]);

            $table->string('complemento')
                ->nullable();
            $table->morphs('anotavel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anotacoes_veiculo', function (Blueprint $table) {
            $table->dropMorphs('anotavel');
            $table->dropColumn('complemento');
        });
    }
};
