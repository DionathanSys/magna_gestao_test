<?php

namespace App\Services\Viagem;

use App\Enum\Viagem\StatusViagemEnum;
use App\Models\Viagem;
use App\Models\ViagemComplemento;
use Illuminate\Support\Facades\Log;

class ViagemComplementoService
{
    public function create(Viagem $viagem): void
    {
        $cargas = $viagem->cargas->pluck('integrado_id')->unique();

        Log::debug('Criando complementos para a viagem: ' . $viagem->id . ' com cargas: ' . implode(', ', $cargas->toArray()), [
            'metodo' => __METHOD__,
        ]);

        foreach ($cargas as $integradoId) {
            $complemento = ViagemComplemento::query()
                ->updateOrCreate(
                    [
                        'viagem_id'     => $viagem->id,
                        'integrado_id'  => $integradoId,
                    ],
                    [
                        'viagem_id'             => $viagem->id,
                        'veiculo_id'            => $viagem->veiculo_id,
                        'numero_viagem'         => $viagem->numero_viagem,
                        'documento_transporte'  => $viagem->documento_transporte,
                        'integrado_id'          => $integradoId,
                        'km_rodado'             => $viagem->km_rodado,
                        'km_pago'               => $viagem->km_pago,
                        'km_divergencia'        => $viagem->km_dispersao,
                        'km_cobrar'             => $viagem->km_cobrar,
                        'data_competencia'      => $viagem->data_competencia,
                        'motivo_divergencia'    => $viagem->motivo_divergencia,
                        'conferido'             => false,
                        'status'                => StatusViagemEnum::PENDENTE,

                ]);

                $viagem->km_rodado = 0;
                $viagem->km_pago = 0;
                $viagem->km_dispersao = 0;
                $viagem->km_cobrar = 0;

                Log::debug('Complemento criado ou atualizado para a viagem: ' . $viagem->id . ' e integrado_id: ' . $integradoId, [
                    'complemento_id' => $complemento->id,
                ]);
        }

    }
}
