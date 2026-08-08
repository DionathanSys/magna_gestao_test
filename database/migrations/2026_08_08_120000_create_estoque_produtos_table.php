<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque_produtos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->decimal('saldo', 14, 4)->default(0);
            $table->decimal('estoque_minimo', 14, 4)->nullable();
            $table->decimal('estoque_maximo', 14, 4)->nullable();
            $table->bigInteger('valor_reposicao_centavos')->default(0);
            $table->bigInteger('custo_total_centavos')->default(0);
            $table->date('ultimo_movimento_em')->nullable();
            $table->unsignedInteger('dias_obsolescencia')->nullable();
            $table->unsignedInteger('previsao_consumo_dias')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nome');
            $table->index('ultimo_movimento_em');
            $table->index('dias_obsolescencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_produtos');
    }
};
