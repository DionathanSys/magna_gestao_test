<?php

namespace App\Services\Pneus;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\MotivoMovimentoPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
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
            $pneu = DB::transaction(function () use ($data) {
                $action = new Actions\CreatePneu;
                $pneu = $action->handle($data);

                if ($pneu === null || $action->hasError) {
                    $this->setError($action->message, $action->errors);

                    return null;
                }

                (new PneuCicloService)->ensureCurrentCycle($pneu);

                return $pneu;
            });

            if ($pneu === null) {
                return null;
            }

            $this->setSuccess('Pneu cadastrado com sucesso.');

            return $pneu;
        } catch (\Throwable $e) {
            Log::error('Erro ao cadastrar pneu', [
                'metodo' => __METHOD__.'-'.__LINE__,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            $this->setError('Erro ao cadastrar pneu. '.$e->getMessage());

            return null;
        }
    }

    public function recapar(array $data): ?Models\Recapagem
    {
        try {

            return DB::transaction(function () use ($data) {

                $data['ciclo_vida'] = (self::getCicloVidaPneu($data['pneu_id']) ?? 0) + 1;

                $action = new Actions\RecaparPneu;
                $recapagem = $action->handle($data);

                if ($recapagem === null || $action->hasError) {
                    $this->setError($action->message, $action->errors);

                    return null;
                }

                if (! self::atualizarCicloVida($recapagem->pneu_id)) {
                    throw new \RuntimeException('Falha ao atualizar ciclo de vida do pneu');
                }

                $pneu = Models\Pneu::query()->findOrFail($recapagem->pneu_id);
                $pneu->refresh();
                $ciclo = (new PneuCicloService)->openCycleFromRecapagem($pneu, $recapagem);
                $recapagem->update(['pneu_ciclo_id' => $ciclo->id]);

                Log::info(__METHOD__.' - Recapagem realizada com sucesso', [
                    'recapagem_id' => $recapagem->id,
                    'pneu_id' => $recapagem->pneu_id,
                ]);

                $this->setSuccess('Recapagem realizada com sucesso.');

                return $recapagem;
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao recapar pneu', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            $this->setError('Erro ao recapar pneu: '.$e->getMessage());

            return null;
        }
    }

    public function reverterRecapagem(Models\Pneu $pneu): bool
    {
        try {
            return DB::transaction(function () use ($pneu) {
                $pneu = Models\Pneu::query()->lockForUpdate()->findOrFail($pneu->id);

                $recapagem = $pneu->recapagens()
                    ->orderByDesc('ciclo_vida')
                    ->orderByDesc('data_recapagem')
                    ->orderByDesc('id')
                    ->first();

                if (! $recapagem) {
                    $this->setError('Este pneu não possui recapagem para reverter.');

                    return false;
                }

                if ((int) $recapagem->ciclo_vida !== (int) $pneu->ciclo_vida) {
                    $this->setError('Só é possível reverter a recapagem da vida atual do pneu.');

                    return false;
                }

                if ((int) $pneu->ciclo_vida <= 0) {
                    $this->setError('Não é possível reverter recapagem de um pneu na vida 0.');

                    return false;
                }

                $cicloService = new PneuCicloService;
                $cicloAtual = $recapagem->ciclo ?: $cicloService->getCurrentCycle($pneu);
                $vidaAnterior = (int) $pneu->ciclo_vida - 1;

                $recapagem->delete();

                $pneu->update(['ciclo_vida' => $vidaAnterior]);
                $pneu->refresh();

                $cicloAnterior = $cicloService->reopenCycle($pneu, $vidaAnterior)
                    ?? $cicloService->ensureCurrentCycle($pneu);

                if ($cicloAtual) {
                    Models\PneuPosicaoVeiculo::query()
                        ->where('pneu_id', $pneu->id)
                        ->where('pneu_ciclo_id', $cicloAtual->id)
                        ->update(['pneu_ciclo_id' => $cicloAnterior->id]);
                }

                if ($cicloAtual && ! $this->cicloHasReferences($cicloAtual)) {
                    $cicloAtual->delete();
                }

                Log::info('Recapagem revertida com sucesso', [
                    'pneu_id' => $pneu->id,
                    'recapagem_id' => $recapagem->id,
                    'ciclo_vida' => $vidaAnterior,
                ]);

                $this->setSuccess('Recapagem revertida com sucesso.');

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao reverter recapagem do pneu', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $e->getMessage(),
                'pneu_id' => $pneu->id,
            ]);
            $this->setError('Erro ao reverter recapagem: '.$e->getMessage());

            return false;
        }
    }

    private function cicloHasReferences(Models\PneuCiclo $ciclo): bool
    {
        return $ciclo->recapagens()->exists()
            || $ciclo->consertos()->exists()
            || $ciclo->historicos()->exists()
            || $ciclo->inspecoes()->exists();
    }

    public static function atualizarCicloVida(int $pneuId): bool
    {
        try {
            $action = new Actions\IncrementCicloVidaPneu;

            return $action->handle($pneuId);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar ciclo de vida do pneu', [
                'metodo' => __METHOD__.'-'.__LINE__,
                'error' => $e->getMessage(),
                'pneu_id' => $pneuId,
            ]);

            return false;
        }
    }

    public static function getCicloVidaPneu(int $pneuId): ?int
    {
        $query = new Queries\GetCicloVidaPneu;
        $ciclo_vida = $query->handle($pneuId);

        if ($ciclo_vida === null) {
            throw new \RuntimeException('Pneu não encontrado para ID: '.$pneuId);
        }

        return $ciclo_vida;
    }

    public function getPneusDisponiveis(?string $search = null): array
    {
        $query = new Queries\GetPneuDisponivel;

        return $query->handle($search);
    }

    public function enviarParaRecapagem(Models\Pneu $pneu): bool
    {
        try {
            $localId = $this->resolveLocalId(LocalPneuEnum::AGUARDANDO_RETORNO_RECAP->value);

            $pneu->update([
                'status' => StatusPneuEnum::INDISPONIVEL,
                'local' => LocalPneuEnum::AGUARDANDO_RETORNO_RECAP,
                'pneu_local_id' => $localId,
            ]);

            $this->setSuccess('Pneu enviado para recapagem.');

            return true;
        } catch (\Throwable $e) {
            $this->setError('Erro ao enviar pneu para recapagem: '.$e->getMessage());

            return false;
        }
    }

    public function receberRetornoRecapagem(Models\Pneu $pneu, array $data): bool
    {
        try {
            return DB::transaction(function () use ($pneu, $data) {
                if (($data['resultado_retorno'] ?? null) === 'RECUSADO') {
                    $pneu->update([
                        'status' => StatusPneuEnum::SUCATA,
                        'local' => LocalPneuEnum::SUCATA,
                        'pneu_local_id' => $this->resolveLocalId(LocalPneuEnum::SUCATA->value),
                    ]);

                    (new PneuCicloService)->closeCurrentCycle($pneu, $data['data_recapagem'] ?? now()->toDateString());
                    $this->setSuccess('Pneu recusado no recap e descartado automaticamente.');

                    return true;
                }

                if (($data['resultado_retorno'] ?? null) === 'RETORNAR_ESTOQUE') {
                    $pneu->update([
                        'status' => StatusPneuEnum::DISPONIVEL,
                        'local' => LocalPneuEnum::ESTOQUE_CCO,
                        'pneu_local_id' => $this->resolveLocalId(LocalPneuEnum::ESTOQUE_CCO->value),
                    ]);

                    $this->setSuccess('Pneu retornou para estoque sem recapagem e permanece no ciclo atual.');

                    return true;
                }

                $recapagem = $this->recapar([
                    'pneu_id' => $pneu->id,
                    'valor' => $data['valor'] ?? 0,
                    'desenho_pneu_id' => $data['desenho_pneu_id'],
                    'data_recapagem' => $data['data_recapagem'],
                    'ignorar_validacao_inspecao' => true,
                ]);

                if (! $recapagem) {
                    return false;
                }

                $pneu->refresh();
                $pneu->update([
                    'status' => StatusPneuEnum::DISPONIVEL,
                    'local' => LocalPneuEnum::ESTOQUE_CCO,
                    'pneu_local_id' => $this->resolveLocalId(LocalPneuEnum::ESTOQUE_CCO->value),
                ]);

                $this->setSuccess('Pneu recebido do recap e liberado para estoque.');

                return true;
            });
        } catch (\Throwable $e) {
            $this->setError('Erro ao receber retorno de recapagem: '.$e->getMessage());

            return false;
        }
    }

    protected function resolveLocalId(string $localNome): ?int
    {
        return Models\PneuLocal::query()
            ->where('nome', $localNome)
            ->value('id');
    }

    public function retornarDeConserto(Models\Pneu $pneu, array $data): bool
    {
        try {
            return DB::transaction(function () use ($pneu, $data) {
                $ultimaRemocao = $pneu->historicoMovimentacao()
                    ->where('motivo', MotivoMovimentoPneuEnum::CONSERTO->value)
                    ->latest('id')
                    ->first();

                Models\Conserto::create([
                    'pneu_id' => $pneu->id,
                    'pneu_ciclo_id' => $pneu->cicloAtual?->id,
                    'ciclo_vida' => $pneu->ciclo_vida,
                    'data_conserto' => $data['data_conserto'],
                    'tipo_conserto' => $data['tipo_conserto'],
                    'valor' => $data['valor'] ?? 0,
                    'garantia' => (bool) ($data['garantia'] ?? false),
                    'parceiro_id' => $data['parceiro_id'] ?? null,
                    'veiculo_id' => $data['veiculo_id'] ?? $ultimaRemocao?->veiculo_id,
                ]);

                if (($data['destino'] ?? 'ESTOQUE_CCO') === 'ESTOQUE_CCO') {
                    $pneu->update([
                        'status' => StatusPneuEnum::DISPONIVEL,
                        'local' => LocalPneuEnum::ESTOQUE_CCO,
                    ]);

                    $this->setSuccess('Retorno do conserto concluído para estoque.');

                    return true;
                }

                if (! $ultimaRemocao) {
                    $this->setError('Não foi encontrada a posição anterior do pneu para reaplicação.');

                    return false;
                }

                $posicao = Models\PneuPosicaoVeiculo::query()
                    ->where('veiculo_id', $ultimaRemocao->veiculo_id)
                    ->where('eixo', $ultimaRemocao->eixo)
                    ->where('posicao', $ultimaRemocao->posicao)
                    ->first();

                if (! $posicao || filled($posicao->pneu_id)) {
                    $this->setError('A posição anterior não está disponível para reaplicação.');

                    return false;
                }

                (new MovimentarPneuService)->aplicarPneu($posicao, [
                    'pneu_id' => $pneu->id,
                    'data_inicial' => $data['data_retorno'],
                    'km_inicial' => $data['km_inicial'],
                    'motivo' => MotivoMovimentoPneuEnum::APLICACAO,
                ]);

                $this->setSuccess('Retorno do conserto concluído para a posição anterior.');

                return true;
            });
        } catch (\Throwable $e) {
            $this->setError('Erro ao retornar pneu do conserto: '.$e->getMessage());

            return false;
        }
    }
}
