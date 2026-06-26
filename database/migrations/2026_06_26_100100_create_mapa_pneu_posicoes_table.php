<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapa_pneu_posicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapa_pneu_id')->constrained('mapas_pneu')->cascadeOnDelete();
            $table->string('codigo', 30);
            $table->string('nome', 120);
            $table->unsignedSmallInteger('sequencia');
            $table->unsignedSmallInteger('eixo_numero')->default(0);
            $table->string('lado', 20)->default('CENTRO');
            $table->string('conjunto', 20)->default('SIMPLES');
            $table->string('tipo_posicao', 30)->default('LIVRE');
            $table->boolean('aceita_pneu_reserva')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['mapa_pneu_id', 'codigo']);
            $table->unique(['mapa_pneu_id', 'sequencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapa_pneu_posicoes');
    }
};
