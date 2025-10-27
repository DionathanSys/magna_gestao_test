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
        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->decimal('valor_liquido', 14, 2)
                ->after('valor_icms')
                ->virtualAs('valor_total - valor_icms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->dropColumn('valor_liquido');
        });
    }
};
