<?php

namespace App\Services\Carga\Actions;

use App\Models;

class AtualizarKmDispersao
{
    public function handle(int $viagemId): void
    {
        $kmDispersao = Models\Viagem::query()
            ->where('id', $viagemId)
            ->sum('km_dispersao');
            
        $totalCargas = Models\CargaViagem::query()
            ->where('viagem_id', $viagemId)
            ->count();

        if ($totalCargas == 0) {
            return;
        }

        Models\CargaViagem::query()
            ->where('viagem_id', $viagemId)
            ->update([
                'km_dispersao' => $totalCargas > 1 ? $kmDispersao / $totalCargas : $kmDispersao,
                'km_dispersao_rateio' => $totalCargas > 1 ? true : false,
            ]);
    }
}
