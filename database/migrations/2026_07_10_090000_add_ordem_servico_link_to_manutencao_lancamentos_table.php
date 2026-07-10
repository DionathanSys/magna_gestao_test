<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manutencao_lancamentos', function (Blueprint $table) {
            $table->foreignId('ordem_servico_id')
                ->nullable()
                ->after('veiculo_id')
                ->constrained('ordens_servico')
                ->nullOnDelete();
            $table->string('tipo_vinculo')->nullable()->after('ordem_servico_id');
            $table->timestamp('vinculado_em')->nullable()->after('tipo_vinculo');
            $table->foreignId('vinculado_por')
                ->nullable()
                ->after('vinculado_em')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('ordem_servico_id');
            $table->index('tipo_vinculo');
        });
    }

    public function down(): void
    {
        Schema::table('manutencao_lancamentos', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->dropForeign(['vinculado_por']);
            $table->dropIndex(['ordem_servico_id']);
            $table->dropIndex(['tipo_vinculo']);
            $table->dropColumn(['ordem_servico_id', 'tipo_vinculo', 'vinculado_em', 'vinculado_por']);
        });
    }
};
