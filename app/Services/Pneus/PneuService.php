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
            $pneu->update([
                'local' => \App\Enum\Pneu\LocalPneuEnum::AGUARDANDO_RETORNO_RECAP,
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
                        'status' => \App\Enum\Pneu\StatusPneuEnum::SUCATA,
                        'local' => \App\Enum\Pneu\LocalPneuEnum::SUCATA,
                    ]);

                    (new PneuCicloService)->closeCurrentCycle($pneu, $data['data_recapagem'] ?? now()->toDateString());
                    $this->setSuccess('Pneu recusado no recap e descartado automaticamente.');

                    return true;
                }

                $recapagem = $this->recapar([
                    'pneu_id' => $pneu->id,
                    'valor' => $data['valor'] ?? 0,
                    'desenho_pneu_id' => $data['desenho_pneu_id'],
                    'data_recapagem' => $data['data_recapagem'],
                ]);

                if (! $recapagem) {
                    return false;
                }

                $pneu->refresh();
                $pneu->update([
                    'status' => \App\Enum\Pneu\StatusPneuEnum::DISPONIVEL,
                    'local' => \App\Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO,
                ]);

                $this->setSuccess('Pneu recebido do recap e liberado para estoque.');

                return true;
            });
        } catch (\Throwable $e) {
            $this->setError('Erro ao receber retorno de recapagem: '.$e->getMessage());

            return false;
        }
    }

    public function retornarDeConserto(Models\Pneu $pneu, array $data): bool
    {
        try {
            return DB::transaction(function () use ($pneu, $data) {
                $ultimaRemocao = $pneu->historicoMovimentacao()
                    ->where('motivo', \App\Enum\Pneu\MotivoMovimentoPneuEnum::CONSERTO->value)
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
                        'status' => \App\Enum\Pneu\StatusPneuEnum::DISPONIVEL,
                        'local' => \App\Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO,
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
                    'motivo' => \App\Enum\Pneu\MotivoMovimentoPneuEnum::APLICACAO,
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
