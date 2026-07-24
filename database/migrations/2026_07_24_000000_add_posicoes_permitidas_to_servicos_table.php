<?php

use App\Enum\OrdemServico\PosicaoItemOrdemServicoEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('servicos')) {
            return;
        }

        if (! Schema::hasColumn('servicos', 'posicoes_permitidas')) {
            Schema::table('servicos', function (Blueprint $table): void {
                $table->json('posicoes_permitidas')
                    ->nullable()
                    ->after('controla_posicao');
            });
        }

        DB::table('servicos')
            ->where('controla_posicao', true)
            ->whereNull('posicoes_permitidas')
            ->update([
                'posicoes_permitidas' => json_encode(PosicaoItemOrdemServicoEnum::values()),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('servicos') || ! Schema::hasColumn('servicos', 'posicoes_permitidas')) {
            return;
        }

        Schema::table('servicos', function (Blueprint $table): void {
            $table->dropColumn('posicoes_permitidas');
        });
    }
};
