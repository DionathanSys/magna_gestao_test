<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garantia_servicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_ordem_servico_id')->unique()->constrained('itens_ordem_servico')->cascadeOnDelete();
            $table->foreignId('item_ordem_servico_anterior_id')->nullable()->constrained('itens_ordem_servico')->nullOnDelete();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->foreignId('ordem_servico_anterior_id')->nullable()->constrained('ordens_servico')->nullOnDelete();
            $table->foreignId('veiculo_id')->constrained('veiculos')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->boolean('controla_posicao')->default(false);
            $table->string('posicao', 20)->nullable();
            $table->unsignedInteger('km_execucao');
            $table->dateTime('data_execucao');
            $table->unsignedInteger('km_execucao_anterior')->nullable();
            $table->dateTime('data_execucao_anterior')->nullable();
            $table->unsignedInteger('km_durabilidade')->nullable();
            $table->unsignedInteger('dias_durabilidade')->nullable();
            $table->unsignedInteger('garantia_km_aplicada');
            $table->unsignedInteger('garantia_dias_aplicada');
            $table->boolean('em_garantia')->default(false);
            $table->string('motivo_alerta')->nullable();
            $table->timestamps();

            $table->index(['veiculo_id', 'servico_id', 'posicao', 'data_execucao'], 'garantia_servico_busca_idx');
            $table->index(['em_garantia', 'data_execucao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garantia_servicos');
    }
};
