<?php

namespace App\Services\Carga\Actions;

use App\Models;

class AtualizarKmDispersao
{
    public function handle(int $viagemId): void
    {
        $viagem = Models\Viagem::query()
            ->select(['id', 'km_dispersao'])
            ->find($viagemId);

        if (! $viagem) {
            return;
        }

        $kmDispersao = (float) ($viagem->km_dispersao ?? 0);
             
        $totalCargas = Models\CargaViagem::query()
            ->where('viagem_id', $viagemId)
            ->count();

        if ($totalCargas == 0) {
            return;
        }

        Models\CargaViagem::query()
            ->where('viagem_id', $viagemId)
            ->update([
                'km_dispersao' => $totalCargas > 1
                    ? $kmDispersao / $totalCargas
                    : $kmDispersao,
                'km_dispersao_rateio' => $totalCargas > 1,
            ]);
    }
}
