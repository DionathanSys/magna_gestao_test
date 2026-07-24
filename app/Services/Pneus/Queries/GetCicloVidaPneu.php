<?php

namespace App\Services\Pneus\Queries;

use App\Models;

class GetCicloVidaPneu
{
    public function handle(int $pneuId): ?int
    {
        $pneu = Models\Pneu::query()
            ->select('id', 'ciclo_vida')
            ->where('id', $pneuId)
            ->first();

        if ($pneu === null) {
            return null;
        }

        return $pneu->ciclo_vida;
    }
}
