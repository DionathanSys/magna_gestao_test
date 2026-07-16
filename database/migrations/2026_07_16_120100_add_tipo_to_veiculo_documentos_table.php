<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_documentos', function (Blueprint $table) {
            $table->string('tipo', 80)->default('outros')->after('veiculo_id');
            $table->index(['tipo', 'data_fim']);
        });

        DB::table('veiculo_documentos')
            ->where('nome', 'Teste de Fumaça')
            ->update(['tipo' => 'teste_fumaca']);

        DB::table('veiculo_documentos')
            ->where('nome', 'Aferição Tacógrafo')
            ->update(['tipo' => 'afericao_tacografo']);
    }

    public function down(): void
    {
        Schema::table('veiculo_documentos', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'data_fim']);
            $table->dropColumn('tipo');
        });
    }
};
