<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use Illuminate\Support\Facades\Auth;

class ViagemConferida
{
    public function handle(Models\Viagem $viagem): Models\Viagem
    {
        $this->validate($viagem);

        $viagem->update([
            'conferido' => true,
            'updated_by' => Auth::user()->id ?? 1,
            'checked_by' => Auth::user()->id ?? 1,
        ]);

        return $viagem;
    }

    private function validate(Models\Viagem $viagem): void
    {
        $viagem->loadMissing('cargas');

        if ($viagem->ignorar) {
            return;
        }

        if ($viagem->possui_pendencia) {
            throw new \InvalidArgumentException('Viagem possui pendências e não pode ser conferida enquanto não for regularizada ou ignorada.');
        }

        if (! $viagem->documento_transporte) {
            throw new \InvalidArgumentException('Viagem não possui documento de transporte.');
        }

        if ((float) $viagem->km_pago <= 0) {
            throw new \InvalidArgumentException('Viagem não possui KM pago.');
        }

        if ((float) $viagem->km_rodado <= 0) {
            throw new \InvalidArgumentException('Viagem não possui KM rodado.');
        }
    }
}
