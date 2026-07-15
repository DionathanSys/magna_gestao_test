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
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->dateTime('iniciado_em');
            $table->dateTime('encerrado_em')->nullable();
            $table->timestamps();

            $table->index(['ordem_servico_id', 'encerrado_em']);
            $table->index(['colaborador_id', 'encerrado_em']);
        });

        Schema::create('ordem_servico_apontamento_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ordem_servico_apontamento_id')
                ->constrained('ordem_servico_apontamentos')
                ->cascadeOnDelete();
            $table->foreignId('item_ordem_servico_id')->constrained('itens_ordem_servico')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ordem_servico_apontamento_id', 'item_ordem_servico_id'], 'apontamento_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_apontamento_itens');
        Schema::dropIfExists('ordem_servico_apontamentos');
    }
};
