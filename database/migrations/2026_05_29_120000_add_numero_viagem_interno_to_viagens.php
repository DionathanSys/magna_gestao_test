<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->string('numero_viagem_interno')
                ->nullable()
                ->after('numero_viagem')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropUnique(['numero_viagem_interno']);
            $table->dropColumn('numero_viagem_interno');
        });
    }
};
