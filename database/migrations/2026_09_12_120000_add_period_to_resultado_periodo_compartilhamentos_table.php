<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultado_periodo_compartilhamentos', function (Blueprint $table) {
            $table->date('data_inicio')->nullable()->after('resultado_periodo_ids');
            $table->date('data_fim')->nullable()->after('data_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('resultado_periodo_compartilhamentos', function (Blueprint $table) {
            $table->dropColumn(['data_inicio', 'data_fim']);
        });
    }
};
