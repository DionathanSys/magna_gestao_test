<?php

namespace App\Services\Pneus;

use App\Models;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PneuService
{
    use ServiceResponseTrait;

    public function create(array $data): ?Models\Pneu
    {
        try {

            $action = new Actions\CreatePneu();
            $pneu = $action->handle($data);

            if ($pneu === null || $action->hasError) {
                $this->setError($action->message, $action->errors);
                return null;
            }

            $this->setSuccess('Pneu cadastrado com sucesso.');
            return $pneu;
        } catch (\Throwable $e) {
            Log::error('Erro ao cadastrar pneu', [
                'metodo' => __METHOD__ . '-' . __LINE__,
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            $this->setError('Erro ao cadastrar pneu. ' . $e->getMessage());
            return null;
        }
    }

    public function recapar(array $data): ?Models\Recapagem
    {
        try {
            
            return DB::transaction(function () use ($data) {

                $data['ciclo_vida'] = self::getCicloVidaPneu($data['pneu_id']) ?? 0;
                
                $action = new Actions\RecaparPneu();
                $recapagem = $action->handle($data);

                if ($recapagem === null || $action->hasError) {
                    $this->setError($action->message, $action->errors);
                    return null;
                }

                if (! self::atualizarCicloVida($recapagem->pneu_id)) {
                    throw new \RuntimeException('Falha ao atualizar ciclo de vida do pneu');
                }

                Log::info(__METHOD__ . ' - Recapagem realizada com sucesso', [
                    'recapagem_id' => $recapagem->id,
                    'pneu_id' => $recapagem->pneu_id,
                ]);

                $this->setSuccess('Recapagem realizada com sucesso.');
                return $recapagem;
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao recapar pneu', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'error'  => $e->getMessage(),
                'data'   => $data
            ]);
            $this->setError('Erro ao recapar pneu: ' . $e->getMessage());
            return null;
        }
    }

    public static function atualizarCicloVida(int $pneuId): bool
    {
        try {
            $action = new Actions\IncrementCicloVidaPneu();
            return $action->handle($pneuId);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar ciclo de vida do pneu', [
                'metodo'    => __METHOD__ . '-' . __LINE__,
                'error'     => $e->getMessage(),
                'pneu_id'   => $pneuId
            ]);
            return false;
        }
    }

    public static function getCicloVidaPneu(int $pneuId): ?int
    {
            $query = new Queries\GetCicloVidaPneu();
            $ciclo_vida = $query->handle($pneuId);

            if ($ciclo_vida === null) {
                throw new \RuntimeException('Pneu não encontrado para ID: ' . $pneuId);
            }

            return $ciclo_vida;
    }

    public function getPneusDisponiveis(): array
    {
        $query = new Queries\GetPneuDisponivel();
        return $query->handle();
    }
}
