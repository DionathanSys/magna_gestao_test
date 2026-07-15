<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordem_servico_apontamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ordem_servico_id');
            $table->foreignId('colaborador_id');
            $table->dateTime('iniciado_em');
            $table->dateTime('encerrado_em')->nullable();
            $table->timestamps();

            $table->foreign('ordem_servico_id', 'fk_osa_ordem_servico')
                ->references('id')
                ->on('ordens_servico')
                ->cascadeOnDelete();
            $table->foreign('colaborador_id', 'fk_osa_colaborador')
                ->references('id')
                ->on('colaboradores')
                ->restrictOnDelete();
            $table->index(['ordem_servico_id', 'encerrado_em'], 'idx_osa_ordem_encerrado');
            $table->index(['colaborador_id', 'encerrado_em'], 'idx_osa_colaborador_encerrado');
        });

        Schema::create('ordem_servico_apontamento_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ordem_servico_apontamento_id');
            $table->foreignId('item_ordem_servico_id');
            $table->timestamps();

            $table->foreign('ordem_servico_apontamento_id', 'fk_osai_apontamento')
                ->references('id')
                ->on('ordem_servico_apontamentos')
                ->cascadeOnDelete();
            $table->foreign('item_ordem_servico_id', 'fk_osai_item')
                ->references('id')
                ->on('itens_ordem_servico')
                ->cascadeOnDelete();

            $table->unique(['ordem_servico_apontamento_id', 'item_ordem_servico_id'], 'apontamento_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_apontamento_itens');
        Schema::dropIfExists('ordem_servico_apontamentos');
    }
};
