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
        Schema::create('resultado_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')
                ->constrained('veiculos')
                ->cascadeOnDelete();
            $table->foreignId('tipo_veiculo_id')
                ->nullable()
                ->constrained('tipo_veiculos')
                ->nullOnDelete();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->integer('km_inicial')
                ->nullable();
            $table->integer('km_final')
                ->nullable();
            $table->integer('km_percorrido')
                ->virtualAs('km_final - km_inicial');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultado_periodos');
    }
};
