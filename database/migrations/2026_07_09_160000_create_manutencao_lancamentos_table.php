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
        Schema::create('manutencao_lancamentos', function (Blueprint $table) {
            $table->id();
            $table->string('sync_key')->unique();
            $table->string('tipo_manutencao');
            $table->date('data_negociacao');
            $table->foreignId('veiculo_id')
                ->constrained('veiculos')
                ->cascadeOnDelete();
            $table->string('placa', 10);
            $table->string('codigo_produto')->nullable();
            $table->string('produto');
            $table->decimal('quantidade', 12, 4)->default(0);
            $table->string('origem')->nullable();
            $table->bigInteger('valor_total_centavos')->default(0);
            $table->bigInteger('valor_unitario_centavos')->default(0);
            $table->string('sequencia');
            $table->string('nr_os_nf')->nullable();
            $table->string('nr_unico');
            $table->string('parceiro')->default('Almoxarifado');
            $table->string('grupo_produto')->nullable();
            $table->string('unidade')->nullable();
            $table->string('codigo_veiculo_erp')->nullable();
            $table->string('codigo_local')->nullable();
            $table->string('local_estoque')->nullable();
            $table->foreignId('import_log_id')
                ->nullable()
                ->constrained('import_logs')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('data_negociacao');
            $table->index('veiculo_id');
            $table->index('placa');
            $table->index('nr_unico');
            $table->index('nr_os_nf');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manutencao_lancamentos');
    }
};
