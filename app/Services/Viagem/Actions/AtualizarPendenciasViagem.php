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
        $divergencias = [];

        if ($qtdeDestino > 1) {
            $possuiPendencia = true;
            $divergencias['multiplos_destinos'] = 'Multiplos destinos';
        }

        if ($kmPago <= 0) {
            $possuiPendencia = true;
            $divergencias['sem_km_pago'] = 'Sem km pago';
        }

        if ($kmRodado <= 0) {
            $possuiPendencia = true;
            $divergencias['sem_km_rodado'] = 'Sem km rodado';
        }

        if ($limiteKm > 0 && $kmRodado > $limiteKm) {
            $possuiPendencia = true;
            $divergencias['km_acima_limite'] = 'Km acima do limite';
        }

        if (! $temCarga) {
            $possuiPendencia = true;
            $divergencias['sem_carga'] = 'Sem carga';
        } elseif ($temCargaSemIntegrado) {
            $possuiPendencia = true;
            $divergencias['carga_sem_integrado'] = 'Carga sem integrado';
        }

        $viagem->updateQuietly([
            'possui_pendencia' => $possuiPendencia,
            'divergencias' => $divergencias,
        ]);

        return $possuiPendencia;
    }
}
