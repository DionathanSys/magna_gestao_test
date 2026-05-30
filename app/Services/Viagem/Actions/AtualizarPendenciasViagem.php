<?php

namespace App\Services\Viagem\Actions;

use App\Models\Viagem;

class AtualizarPendenciasViagem
{
    public function handle(Viagem $viagem): bool
    {
        $viagem->loadMissing('cargas');

        $limiteKm = (float) db_config('config-viagem.km_rodado_maximo_alerta', 1000);
        $kmRodado = (float) ($viagem->km_rodado ?? 0);
        $kmPago = (float) ($viagem->km_pago ?? 0);
        $qtdeDestino = (int) ($viagem->qtde_destino_viagem ?? 0);

        $possuiPendencia = false;

        if ($qtdeDestino > 1) {
            $possuiPendencia = true;
        }

        if ($kmRodado <= 0 || $kmPago <= 0) {
            $possuiPendencia = true;
        }

        if ($limiteKm > 0 && $kmRodado > $limiteKm) {
            $possuiPendencia = true;
        }

        if ($viagem->cargas->isEmpty() || $viagem->cargas->contains(fn ($carga) => blank($carga->integrado_id))) {
            $possuiPendencia = true;
        }

        $viagem->updateQuietly([
            'possui_pendencia' => $possuiPendencia,
        ]);

        return $possuiPendencia;
    }
}
