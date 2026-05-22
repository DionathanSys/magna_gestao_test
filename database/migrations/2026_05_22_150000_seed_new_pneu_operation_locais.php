<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['nome' => 'AGUARDANDO RECAPAGEM', 'tipo' => 'RECAPAGEM'],
            ['nome' => 'AGUARDANDO RETORNO RECAP', 'tipo' => 'RECAPAGEM'],
        ] as $local) {
            DB::table('pneu_locais')->updateOrInsert(
                ['nome' => $local['nome']],
                [
                    'tipo' => $local['tipo'],
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('pneu_locais')
            ->whereIn('nome', ['AGUARDANDO RECAPAGEM', 'AGUARDANDO RETORNO RECAP'])
            ->delete();
    }
};
