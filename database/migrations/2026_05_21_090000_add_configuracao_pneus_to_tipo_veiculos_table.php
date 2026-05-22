<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_veiculos', function (Blueprint $table) {
            if (! Schema::hasColumn('tipo_veiculos', 'configuracao_pneus')) {
                $table->string('configuracao_pneus', 10)->nullable()->after('descricao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tipo_veiculos', function (Blueprint $table) {
            if (Schema::hasColumn('tipo_veiculos', 'configuracao_pneus')) {
                $table->dropColumn('configuracao_pneus');
            }
        });
    }
};
