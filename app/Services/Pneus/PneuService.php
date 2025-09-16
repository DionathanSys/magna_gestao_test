<?php

namespace App\Services\Pneus;

use App\Models;
use Illuminate\Support\Facades\Log;

class PneuService
{
    public function create(array $data): ?Models\Pneu
    {
        return Models\Pneu::create($data);
    }

    public static function atualizarCicloVida(Models\Recapagem $recapagem)
    {
        Log::debug('Atualizando ciclo de vida do pneu', [
            'pneu_id' => $recapagem->pneu_id,
            'recapagem_id' => $recapagem->id,
        ]);
        $pneu = Models\Pneu::find($recapagem->pneu_id);
        $pneu->ciclo_vida = $pneu->ciclo_vida + 1;
        $pneu->save();
    }


}
