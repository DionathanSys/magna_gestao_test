<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrado_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrado_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('alias_normalizado')->unique();
            $table->timestamps();
        });

        Schema::table('cargas_viagem', function (Blueprint $table) {
            $table->string('destino_externo')->nullable()->after('documento_transporte');
            $table->string('destino_normalizado')->nullable()->after('destino_externo');
            $table->unique(['viagem_id', 'destino_normalizado'], 'cargas_viagem_destino_unico');
        });
    }

    public function down(): void
    {
        Schema::table('cargas_viagem', function (Blueprint $table) {
            $table->dropUnique('cargas_viagem_destino_unico');
            $table->dropColumn(['destino_externo', 'destino_normalizado']);
        });

        Schema::dropIfExists('integrado_aliases');
    }
};
