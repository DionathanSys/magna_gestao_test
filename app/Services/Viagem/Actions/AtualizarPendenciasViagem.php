<?php

namespace App\Services\Viagem\Actions;

use App\Models\CargaViagem;
use App\Models\Viagem;

class AtualizarPendenciasViagem
{
    public function handle(Viagem $viagem): bool
    {
        $limiteKm = (float) db_config('config-viagem.km_rodado_maximo_alerta', 1000);
        $kmRodado = (float) ($viagem->km_rodado ?? 0);
        $kmPago = (float) ($viagem->km_pago ?? 0);
        $qtdeDestino = (int) ($viagem->total_destinos ?? 0);
        $temCarga = CargaViagem::query()->where('viagem_id', $viagem->id)->exists();
        $temCargaSemIntegrado = CargaViagem::query()
            ->where('viagem_id', $viagem->id)
            ->whereNull('integrado_id')
            ->exists();

        $possuiPendencia = false;
        $pendencias = [];

        if ($qtdeDestino > 1) {
            $possuiPendencia = true;
            $pendencias['multiplos_destinos'] = 'Multiplos destinos';
        }

        if ($kmPago <= 0) {
            $possuiPendencia = true;
            $pendencias['sem_km_pago'] = 'Sem km pago';
        }

        if ($kmRodado <= 0) {
            $possuiPendencia = true;
            $pendencias['sem_km_rodado'] = 'Sem km rodado';
        }

        if ($limiteKm > 0 && $kmRodado > $limiteKm) {
            $possuiPendencia = true;
            $pendencias['km_acima_limite'] = 'Km acima do limite';
        }

        if (! $temCarga || $temCargaSemIntegrado) {
            $possuiPendencia = true;
            $pendencias['sem_integrado'] = 'Sem integrado';
        }

        $viagem->updateQuietly([
            'possui_pendencia' => $possuiPendencia,
            'pendencias' => $pendencias,
        ]);

        return $possuiPendencia;
    }
}
