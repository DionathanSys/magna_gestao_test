<?php

namespace App\Services\Pneus;

use App\Models\Pneu;
use App\Models\Recapagem;
use Illuminate\Support\Facades\Log;

class PneuService
{
    public static function atualizarCicloVida(Recapagem $recapagem)
    {
        Log::debug('Atualizando ciclo de vida do pneu', [
            'pneu_id' => $recapagem->pneu_id,
            'recapagem_id' => $recapagem->id,
        ]);
        $pneu = Pneu::find($recapagem->pneu_id);
        $pneu->ciclo_vida = $pneu->ciclo_vida + 1;
        $pneu->save();
    }
}
