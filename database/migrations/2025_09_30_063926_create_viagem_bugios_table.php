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
        Schema::create('viagens_bugio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')->constrained('veiculos');
            $table->json('destinos')->nullable();
            $table->decimal('km_rodado', 10, 2)->nullable()->default(0);
            $table->decimal('km_pago', 10, 2)->nullable()->default(0);
            $table->decimal('km_dispersao', 10, 2)
                ->virtualAs("COALESCE(km_rodado, 0) - COALESCE(km_pago, 0)");
            $table->decimal('dispersao_percentual', 10, 2)
                ->virtualAs("(COALESCE(km_dispersao, 0) / NULLIF(COALESCE(km_rodado, 0), 0)) * 100");
            $table->date('data_competencia')->nullable();
            $table->decimal('frete', 10, 2)->nullable()->default(0);
            $table->boolean('checked')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('checked_by')->constrained('users');
            $table->string('observacao')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viagens_bugio');
    }
};
