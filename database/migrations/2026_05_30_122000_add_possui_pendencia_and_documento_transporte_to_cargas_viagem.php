<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (! Schema::hasColumn('viagens', 'possui_pendencia')) {
                $table->boolean('possui_pendencia')->default(false)->after('ignorar_viagem');
            }
        });

        Schema::table('cargas_viagem', function (Blueprint $table) {
            if (! Schema::hasColumn('cargas_viagem', 'documento_transporte')) {
                $table->string('documento_transporte')->nullable()->after('viagem_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cargas_viagem', function (Blueprint $table) {
            if (Schema::hasColumn('cargas_viagem', 'documento_transporte')) {
                $table->dropColumn('documento_transporte');
            }
        });

        Schema::table('viagens', function (Blueprint $table) {
            if (Schema::hasColumn('viagens', 'possui_pendencia')) {
                $table->dropColumn('possui_pendencia');
            }
        });
    }
};
