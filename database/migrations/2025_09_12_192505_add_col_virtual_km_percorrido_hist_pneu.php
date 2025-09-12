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
        Schema::table('historico_movimento_pneus', function (Blueprint $table) {
            $table->decimal('km_percorrido', 10, 2)
                ->virtualAs('COALESCE(km_final, 0) - COALESCE(km_inicial, 0)')
                ->after('km_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_movimento_pneus', function (Blueprint $table) {
            $table->dropColumn('km_percorrido');
        });
    }
};
