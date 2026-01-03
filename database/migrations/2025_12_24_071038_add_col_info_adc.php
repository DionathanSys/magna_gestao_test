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
            $table->json('info_adicionais')
                ->nullable()
                ->after('observacao');
            $table->json('anexos')
                ->nullable()
                ->after('observacao');
            $table->string('nro_documento')
                ->nullable()
                ->after('nro_notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens_bugio', function (Blueprint $table) {
            $table->dropColumn('info_adicionais');
            $table->dropColumn('anexos');
            $table->dropColumn('nro_documento');
        });
    }
};
