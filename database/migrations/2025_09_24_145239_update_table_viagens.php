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
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropColumn('numero_custo_frete');
            $table->dropColumn('tipo_viagem');
            $table->dropColumn('valor_frete');
            $table->dropColumn('valor_cte');
            $table->dropColumn('valor_nfs');
            $table->dropColumn('valor_icms');
            $table->dropColumn('km_divergencia');
            $table->dropColumn('km_rota_corrigido');
            $table->dropColumn('km_pago_excedente');
            $table->dropColumn('km_rodado_excedente');
            $table->dropColumn('peso');
            $table->dropColumn('entregas');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
        });
    }
};
