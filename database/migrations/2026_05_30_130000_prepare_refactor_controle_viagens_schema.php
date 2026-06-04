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

        DB::table('viagens')
            ->select([
                'id',
                'numero_viagem_interno',
                'qtde_destino_viagem',
                'ignorar_viagem',
                'divergencias',
                'condutor',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($viagens): void {
                foreach ($viagens as $viagem) {
                    DB::table('viagens')
                        ->where('id', $viagem->id)
                        ->update([
                            'numero_interno' => $viagem->numero_viagem_interno,
                            'total_destinos' => $viagem->qtde_destino_viagem,
                            'ignorar' => (bool) $viagem->ignorar_viagem,
                            'pendencias' => $viagem->divergencias,
                            'motorista1' => $viagem->condutor,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            foreach (['numero_interno', 'total_destinos', 'ignorar', 'pendencias', 'motorista1', 'motorista2'] as $column) {
                if (Schema::hasColumn('viagens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
