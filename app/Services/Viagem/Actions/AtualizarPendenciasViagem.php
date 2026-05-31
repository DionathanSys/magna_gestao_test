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
        $qtdeDestino = (int) ($viagem->qtde_destino_viagem ?? 0);
        $temCarga = CargaViagem::query()->where('viagem_id', $viagem->id)->exists();
        $temCargaSemIntegrado = CargaViagem::query()
            ->where('viagem_id', $viagem->id)
            ->whereNull('integrado_id')
            ->exists();

        $possuiPendencia = false;
        $pendencias = [];

        if ($qtdeDestino > 1) {
            $possuiPendencia = true;
            $pendencias[] = 'Multiplos destinos';
        }

        if ($kmPago <= 0) {
            $possuiPendencia = true;
            $pendencias[] = 'Sem km pago';
        }

        if ($kmRodado <= 0) {
            $possuiPendencia = true;
            $pendencias[] = 'Sem km rodado';
        }

        if ($limiteKm > 0 && $kmRodado > $limiteKm) {
            $possuiPendencia = true;
            $pendencias[] = 'Km acima do limite';
        }

        if (! $temCarga) {
            $possuiPendencia = true;
            $pendencias[] = 'Sem carga';
        } elseif ($temCargaSemIntegrado) {
            $possuiPendencia = true;
            $pendencias[] = 'Carga sem integrado';
        }

        $viagem->updateQuietly([
            'possui_pendencia' => $possuiPendencia,
            'pendencias_resumo' => empty($pendencias) ? 'Sem pendencias' : implode('; ', array_unique($pendencias)),
        ]);

        return $possuiPendencia;
    }
}
