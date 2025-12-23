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
        Schema::table('viagens_bugio', function (Blueprint $table) {
            $table->json('nro_notas')->nullable()->after('destinos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens_bugio', function (Blueprint $table) {
            $table->dropColumn('nro_notas');
        });
    }
};
