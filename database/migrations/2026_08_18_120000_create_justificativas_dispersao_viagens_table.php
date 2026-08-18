<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justificativas_dispersao_viagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viagem_id')->constrained('viagens')->cascadeOnDelete();
            $table->string('motivo');
            $table->text('observacao')->nullable();
            $table->string('numero_viagem')->nullable();
            $table->string('veiculo_placa', 20)->nullable();
            $table->date('data_competencia')->nullable();
            $table->decimal('km_rodado', 10, 2)->default(0);
            $table->decimal('km_pago', 10, 2)->default(0);
            $table->decimal('km_dispersao', 10, 2)->default(0);
            $table->decimal('dispersao_percentual', 8, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['motivo', 'data_competencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificativas_dispersao_viagens');
    }
};
