<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('viagens')
            ->select(['id', 'qtde_destino_viagem', 'km_pago', 'km_rodado'])
            ->orderBy('id')
            ->chunkById(500, function ($viagens): void {
                foreach ($viagens as $viagem) {
                    $divergencias = [];
                    $possuiPendencia = false;

                    if ((int) ($viagem->qtde_destino_viagem ?? 0) > 1) {
                        $possuiPendencia = true;
                        $divergencias['multiplos_destinos'] = 'Multiplos destinos';
                    }

                    if ((float) ($viagem->km_pago ?? 0) <= 0) {
                        $possuiPendencia = true;
                        $divergencias['sem_km_pago'] = 'Sem km pago';
                    }

                    if ((float) ($viagem->km_rodado ?? 0) <= 0) {
                        $possuiPendencia = true;
                        $divergencias['sem_km_rodado'] = 'Sem km rodado';
                    }

                    if (! DB::table('cargas_viagem')->where('viagem_id', $viagem->id)->exists() || DB::table('cargas_viagem')->where('viagem_id', $viagem->id)->whereNull('integrado_id')->exists()) {
                        $possuiPendencia = true;
                        $divergencias['sem_integrado'] = 'Sem integrado';
                    }

                    DB::table('viagens')
                        ->where('id', $viagem->id)
                        ->update([
                            'possui_pendencia' => $possuiPendencia,
                            'divergencias' => json_encode($divergencias),
                        ]);
                }
            });
    }

    public function down(): void
    {
    }
};
