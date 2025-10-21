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
        Schema::create('abastecimentos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_abastecimento')
                ->unique();
            $table->foreignId('veiculo_id')->constrained('veiculos');
            $table->string('quilometragem');
            $table->string('posto_combustivel');
            $table->string('tipo_combustivel');
            $table->decimal('quantidade', 8, 2);
            $table->decimal('preco_por_litro', 10, 0)
                ->comment('Preço por litro em centavos');
            $table->decimal('preco_total', 12, 0)
                ->virtualAs('quantidade * preco_por_litro')
                ->comment('Preço total em centavos');
            $table->dateTime('data_abastecimento');
            $table->boolean('considerar_fechamento')
                ->default(true);
            $table->boolean('considerar_calculo_medio')
                ->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abastecimentos');
    }
};
