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
        Schema::table('recapagens', function (Blueprint $table) {
            $table->string('ciclo_vida', 1)
                ->nullable()
                ->after('desenho_pneu_id');
        });
        Schema::table('consertos', function (Blueprint $table) {
            $table->string('ciclo_vida', 1)
                ->nullable()
                ->after('tipo_conserto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recapagens', function (Blueprint $table) {
            $table->dropColumn('ciclo_vida');
        });
        
        Schema::table('consertos', function (Blueprint $table) {
            $table->dropColumn('ciclo_vida');
        });
    }
};
