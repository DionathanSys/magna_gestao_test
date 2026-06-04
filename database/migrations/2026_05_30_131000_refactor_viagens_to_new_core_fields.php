<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (! Schema::hasColumn('viagens', 'numero_interno')) {
                $table->string('numero_interno')->nullable()->after('numero_viagem');
            }

            if (! Schema::hasColumn('viagens', 'total_destinos')) {
                $table->unsignedInteger('total_destinos')->nullable()->after('data_fim');
            }

            if (! Schema::hasColumn('viagens', 'ignorar')) {
                $table->boolean('ignorar')->default(false)->after('conferido');
            }

            if (! Schema::hasColumn('viagens', 'pendencias')) {
                $table->json('pendencias')->nullable()->after('possui_pendencia');
            }

            if (! Schema::hasColumn('viagens', 'motorista1')) {
                $table->string('motorista1')->nullable()->after('pendencias');
            }

            if (! Schema::hasColumn('viagens', 'motorista2')) {
                $table->string('motorista2')->nullable()->after('motorista1');
            }
        });

        DB::table('viagens')->update([
            'numero_interno' => DB::raw('COALESCE(numero_interno, numero_viagem_interno)'),
            'total_destinos' => DB::raw('COALESCE(total_destinos, qtde_destino_viagem)'),
            'ignorar' => DB::raw('COALESCE(ignorar, ignorar_viagem)'),
            'pendencias' => DB::raw('COALESCE(pendencias, divergencias)'),
            'motorista1' => DB::raw('COALESCE(motorista1, condutor)'),
        ]);

        Schema::table('viagens', function (Blueprint $table) {
            foreach (['numero_viagem_interno', 'qtde_destino_viagem', 'ignorar_viagem', 'divergencias', 'condutor', 'km_cadastro', 'motivo_divergencia', 'considerar_relatorio', 'pendencias_resumo'] as $column) {
                if (Schema::hasColumn('viagens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (! Schema::hasColumn('viagens', 'numero_viagem_interno')) {
                $table->string('numero_viagem_interno')->nullable();
            }
            if (! Schema::hasColumn('viagens', 'qtde_destino_viagem')) {
                $table->unsignedInteger('qtde_destino_viagem')->nullable();
            }
            if (! Schema::hasColumn('viagens', 'ignorar_viagem')) {
                $table->boolean('ignorar_viagem')->default(false);
            }
            if (! Schema::hasColumn('viagens', 'divergencias')) {
                $table->json('divergencias')->nullable();
            }
            if (! Schema::hasColumn('viagens', 'condutor')) {
                $table->string('condutor')->nullable();
            }
            if (! Schema::hasColumn('viagens', 'km_cadastro')) {
                $table->decimal('km_cadastro', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('viagens', 'motivo_divergencia')) {
                $table->string('motivo_divergencia')->nullable();
            }
            if (! Schema::hasColumn('viagens', 'considerar_relatorio')) {
                $table->boolean('considerar_relatorio')->default(true);
            }
            if (! Schema::hasColumn('viagens', 'pendencias_resumo')) {
                $table->text('pendencias_resumo')->nullable();
            }
        });

        DB::table('viagens')->update([
            'numero_viagem_interno' => DB::raw('COALESCE(numero_viagem_interno, numero_interno)'),
            'qtde_destino_viagem' => DB::raw('COALESCE(qtde_destino_viagem, total_destinos)'),
            'ignorar_viagem' => DB::raw('COALESCE(ignorar_viagem, ignorar)'),
            'divergencias' => DB::raw('COALESCE(divergencias, pendencias)'),
            'condutor' => DB::raw('COALESCE(condutor, motorista1)'),
        ]);

        Schema::table('viagens', function (Blueprint $table) {
            foreach (['numero_interno', 'total_destinos', 'ignorar', 'pendencias', 'motorista1', 'motorista2'] as $column) {
                if (Schema::hasColumn('viagens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
