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
            $table->json('anexos')->nullable()->after('observacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_movimento_pneus', function (Blueprint $table) {
            $table->dropColumn('anexos');
        });
    }
};
