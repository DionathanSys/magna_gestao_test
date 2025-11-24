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
        Schema::create('custo_diversos', function (Blueprint $table) {
            $table->id();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->json('descricao');
            $table->decimal('custo_total', 15, 2);
            $table->integer('quantidade_veiculos')
                ->default(0);
            $table->decimal('custo_medio_por_veiculo', 15, 2)
                ->virtualAs('CASE WHEN quantidade_veiculos = 0 THEN 0 ELSE custo_total / quantidade_veiculos END');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custo_diversos');
    }
};
