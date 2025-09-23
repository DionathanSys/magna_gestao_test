<?php

namespace App\Services\Pneus;

use App\Models;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class PneuService
{
    use ServiceResponseTrait;

    public function create(array $data): ?Models\Pneu
    {
        try {

            $action = new Actions\CreatePneu();
            $pneu = $action->handle($data);
            $this->setSuccess('Pneu criado com sucesso.');
            return $pneu;

        } catch (\Exception $e) {

            Log::error('Erro ao criar pneu', [
                'metodo' => __METHOD__.'-'.__LINE__,
                'error' => $e->getMessage(),
                'data' => $data]);

            $this->setError('Erro ao criar pneu: ' . $e->getMessage());
            return null;
        }

    }

    public function recapar(array $data): ?Models\Recapagem
    {
        try {

            Log::debug(__METHOD__ . ' - Iniciando recapagem do pneu', ['data' => $data]);

            $action = new Actions\RecaparPneu();
            $recapagem = $action->handle($data);

            self::atualizarCicloVida($recapagem);
            $this->setSuccess('Recapagem realizada com sucesso.');
            return $recapagem;

        } catch (\Exception $e) {

            Log::error('Erro ao recapar pneu', [
                'metodo' => __METHOD__.'-'.__LINE__,
                'error' => $e->getMessage(),
                'data' => $data]);

            $this->setError('Erro ao recapar pneu: ' . $e->getMessage());
            return null;
        }

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

    public function getPneusDisponiveis(): array
    {
        $query = new Queries\GetPneuDisponivel();
        return $query->handle();
    }




}
