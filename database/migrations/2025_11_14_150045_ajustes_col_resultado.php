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
            $table->foreignId('resultado_periodo_id')
                ->nullable()
                ->constrained('resultado_periodos')
                ->nullOnDelete();
        });

        Schema::table('abastecimentos', function (Blueprint $table) {
            $table->foreignId('resultado_periodo_id')
                ->nullable()
                ->constrained('resultado_periodos')
                ->nullOnDelete();
        });

        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->foreignId('resultado_periodo_id')
                ->nullable()
                ->constrained('resultado_periodos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropForeign(['resultado_periodo_id']);
            $table->dropColumn('resultado_periodo_id');
        });

        Schema::table('abastecimentos', function (Blueprint $table) {
            $table->dropForeign(['resultado_periodo_id']);
            $table->dropColumn('resultado_periodo_id');
        });

        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->dropForeign(['resultado_periodo_id']);
            $table->dropColumn('resultado_periodo_id');
        });
    }
};
