<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pneu_modelos', function (Blueprint $table) {
            if (! Schema::hasColumn('pneu_modelos', 'pneu_marca_id')) {
                $table->foreignId('pneu_marca_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('pneu_marcas')
                    ->nullOnDelete();
            }
        });

        $this->backfillModelBrands();

        Schema::table('pneu_modelos', function (Blueprint $table) {
            try {
                $table->dropUnique('pneu_modelos_nome_unique');
            } catch (Throwable) {
            }

            $table->unique(['pneu_marca_id', 'nome'], 'pneu_modelos_marca_nome_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pneu_modelos', function (Blueprint $table) {
            try {
                $table->dropUnique('pneu_modelos_marca_nome_unique');
            } catch (Throwable) {
            }

            $table->unique('nome', 'pneu_modelos_nome_unique');

            if (Schema::hasColumn('pneu_modelos', 'pneu_marca_id')) {
                $table->dropConstrainedForeignId('pneu_marca_id');
            }
        });
    }

    protected function backfillModelBrands(): void
    {
        DB::table('pneu_modelos')
            ->orderBy('id')
            ->chunkById(200, function (Collection $modelos): void {
                foreach ($modelos as $modelo) {
                    $marcaId = DB::table('pneus')
                        ->where('pneu_modelo_id', $modelo->id)
                        ->whereNotNull('pneu_marca_id')
                        ->orderBy('id')
                        ->value('pneu_marca_id');

                    if (! $marcaId) {
                        continue;
                    }

                    DB::table('pneu_modelos')
                        ->where('id', $modelo->id)
                        ->update(['pneu_marca_id' => $marcaId]);
                }
            });
    }
};
