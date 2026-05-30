<?php

namespace App\Services\Viagem\Actions;

use App\Enum;
use App\Models;
use Illuminate\Support\Facades\Auth;

class ViagemConferida
{

    public function handle(Models\Viagem $viagem): Models\Viagem
    {
        $this->validate($viagem);

        $viagem->update([
            'motivo_divergencia'    => $viagem->motivo_divergencia ?? Enum\MotivoDivergenciaViagem::SEM_OBS,
            'conferido'             => true,
            'updated_by'            => Auth::user()->id ?? 1,
            'checked_by'            => Auth::user()->id ?? 1
        ]);

        return $viagem;
    }

    private function validate(Models\Viagem $viagem): void
    {
        $viagem->loadMissing('cargas');

        if ($viagem->ignorar_viagem) {
            return;
        }

        if ($viagem->possui_pendencia) {
            throw new \InvalidArgumentException('Viagem possui pendências e não pode ser conferida enquanto não for regularizada ou ignorada.');
        }

        if ($viagem->motivo_divergencia == Enum\MotivoDivergenciaViagem::SEM_OBS || $viagem->motivo_divergencia == null) {

            if (! $viagem->documento_transporte) {
                throw new \InvalidArgumentException('Viagem não possui documento de transporte.');
            }

            if ($viagem->km_pago <= 0) {
                throw new \InvalidArgumentException('Viagem não possui KM pago.');
            }

            if ($viagem->km_rodado <= 0) {
                throw new \InvalidArgumentException('Viagem não possui KM rodado.');
            }
        }

        if ($viagem->cargas->count() == 1 && $viagem->km_pago != $viagem->km_cadastro) {
            throw new \InvalidArgumentException('Divergência de KM entre pago e cadastro.');
        }
    }
}
