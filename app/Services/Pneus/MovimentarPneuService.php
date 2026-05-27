<?php

namespace App\Services\Pneus;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\MotivoMovimentoPneuEnum;
use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Models;
use App\Models\PneuPosicaoVeiculo;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimentarPneuService
{
    use ServiceResponseTrait;

    public function __construct(
        protected ?PneuCicloService $cicloService = null,
        protected ?PneuInspecaoService $inspecaoService = null,
    ) {
        $this->cicloService ??= new PneuCicloService;
        $this->inspecaoService ??= new PneuInspecaoService;
    }

    public function inverterPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {
        DB::transaction(function () use ($pneuVeiculo, $data) {
            Log::debug(__METHOD__.' - Iniciando inversão do pneu.', [
                'pneu_veiculo_id' => $pneuVeiculo->id,
                'pneu_id' => $pneuVeiculo->pneu_id,
                'veiculo_id' => $pneuVeiculo->veiculo_id,
                'posicao' => $pneuVeiculo->posicao,
                'eixo' => $pneuVeiculo->eixo,
                'data' => $data,
            ]);

            $pneuId = $pneuVeiculo->pneu_id;

            $this->removerPneu($pneuVeiculo, [
                'data_final' => $data['data_movimento'],
                'km_final' => $data['km_movimento'],
                'sulco' => $data['sulco'] ?? 0,
                'motivo' => MotivoMovimentoPneuEnum::INVERSAO,
                'observacao' => $data['observacao'] ?? null,
                'anexos' => $data['anexos'] ?? null,
            ]);

            $this->aplicarPneu($pneuVeiculo, [
                'pneu_id' => $pneuId,
                'data_inicial' => $data['data_movimento'],
                'km_inicial' => $data['km_movimento'],
                'motivo' => MotivoMovimentoPneuEnum::INVERSAO,
                'sulco' => $data['sulco'] ?? 0,
                'observacao' => $data['observacao'] ?? null,
                'anexos' => $data['anexos'] ?? null,
            ]);

            Log::info(__METHOD__.' - Inversão do pneu finalizada.', [
                'pneu_veiculo' => $pneuVeiculo,
                'pneu' => $pneuVeiculo->pneu,
            ]);
        }, 3);
    }

    public function removerPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {
        $pneu = $pneuVeiculo->pneu()->first();

        if (! $pneu) {
            throw new \DomainException('Nenhum pneu aplicado foi encontrado para esta posição.');
        }

        Log::info(__METHOD__.' - Removendo pneu.', [
            'pneu_veiculo_id' => $pneuVeiculo->id,
            'pneu_id' => $pneuVeiculo->pneu_id,
            'veiculo_id' => $pneuVeiculo->veiculo_id,
            'posicao' => $pneuVeiculo->posicao,
            'eixo' => $pneuVeiculo->eixo,
            'data' => $data,
        ]);

        if ($pneuVeiculo->km_inicial > $data['km_final']) {
            throw new \Exception('A KM final não pode ser menor que a KM inicial.');
        }

        $this->registrarHistoricoMovimento([
            'pneu_id' => $pneuVeiculo->pneu_id,
            'pneu_ciclo_id' => $pneuVeiculo->pneu_ciclo_id ?: $this->cicloService->getCurrentCycle($pneu)?->id,
            'pneu_posicao_veiculo_id' => $pneuVeiculo->id,
            'veiculo_id' => $pneuVeiculo->veiculo_id,
            'data_inicial' => $pneuVeiculo->data_inicial,
            'km_inicial' => $pneuVeiculo->km_inicial,
            'eixo' => $pneuVeiculo->eixo,
            'posicao' => $pneuVeiculo->posicao,
            'motivo' => $data['motivo'],
            'tipo_evento' => 'REMOCAO',
            'sulco_movimento' => $data['sulco'],
            'data_final' => $data['data_final'],
            'km_final' => $data['km_final'],
            'ciclo_vida' => $pneu->ciclo_vida,
            'observacao' => $data['observacao'],
            'anexos' => $data['anexos'] ?? null,
        ]);

        $this->registrarInspecaoMovimentacao($pneuVeiculo, $data);

        $pneuVeiculo->update([
            'pneu_id' => null,
            'pneu_ciclo_id' => null,
            'data_inicial' => null,
            'km_inicial' => null,
        ]);

        switch ($data['motivo']) {
            case MotivoMovimentoPneuEnum::CONSERTO->value:
                $pneu->update([
                    'status' => StatusPneuEnum::INDISPONIVEL,
                    'local' => LocalPneuEnum::MANUTENCAO,
                ]);
                break;
            case MotivoMovimentoPneuEnum::RECAPAGEM->value:
                $pneu->update([
                    'status' => StatusPneuEnum::INDISPONIVEL,
                    'local' => LocalPneuEnum::AGUARDANDO_RECAPAGEM,
                ]);
                break;

            case MotivoMovimentoPneuEnum::ESTEPE->value:
                $pneu->update([
                    'status' => StatusPneuEnum::DISPONIVEL,
                    'local' => LocalPneuEnum::ESTOQUE_CCO,
                ]);
                break;
            case MotivoMovimentoPneuEnum::SUCATEAR->value:
                $pneu->update([
                    'status' => StatusPneuEnum::SUCATA,
                    'local' => LocalPneuEnum::SUCATA,
                ]);
                $this->cicloService->closeCurrentCycle($pneu, $data['data_final'], $data['km_final']);
                break;
            default:
                $pneu->update([
                    'status' => StatusPneuEnum::DISPONIVEL,
                    'local' => LocalPneuEnum::ESTOQUE_CCO,
                ]);
                break;
        }

        Log::info(__METHOD__.' - Pneus após remoção.', [
            'pneu_veiculo' => $pneuVeiculo,
            'pneu' => $pneuVeiculo->pneu,
        ]);
    }

    public function aplicarPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {
        Log::info(__METHOD__.' - Aplicando pneu.', [
            'pneu_veiculo_id' => $pneuVeiculo->id,
            'pneu_id' => $data['pneu_id'],
            'veiculo_id' => $pneuVeiculo->veiculo_id,
            'posicao' => $pneuVeiculo->posicao,
            'eixo' => $pneuVeiculo->eixo,
            'data' => $data,
        ]);

        $pneu = Models\Pneu::query()->findOrFail($data['pneu_id']);
        $mensagemErro = $this->inspecaoService->validarAplicacao($pneu);
        $motivo = $data['motivo'] ?? MotivoMovimentoPneuEnum::APLICACAO;

        if ($mensagemErro) {
            throw new \DomainException($mensagemErro);
        }

        $ciclo = $this->cicloService->ensureCurrentCycle($pneu);

        $this->registrarHistoricoMovimento([
            'pneu_id' => $pneu->id,
            'pneu_ciclo_id' => $ciclo->id,
            'pneu_posicao_veiculo_id' => $pneuVeiculo->id,
            'veiculo_id' => $pneuVeiculo->veiculo_id,
            'data_inicial' => $data['data_inicial'],
            'km_inicial' => $data['km_inicial'],
            'eixo' => $pneuVeiculo->eixo,
            'posicao' => $pneuVeiculo->posicao,
            'motivo' => $motivo instanceof \BackedEnum ? $motivo->value : $motivo,
            'tipo_evento' => 'APLICACAO',
            'sulco_movimento' => $data['sulco'] ?? $pneu->sulco_inicial ?? 0,
            'data_final' => $data['data_inicial'],
            'km_final' => $data['km_inicial'],
            'ciclo_vida' => $pneu->ciclo_vida,
            'observacao' => $data['observacao'] ?? null,
            'anexos' => $data['anexos'] ?? null,
        ]);

        $pneuVeiculo->update([
            'pneu_id' => $data['pneu_id'],
            'pneu_ciclo_id' => $ciclo->id,
            'data_inicial' => $data['data_inicial'],
            'km_inicial' => $data['km_inicial'],
        ]);

        $pneuVeiculo->pneu()->update([
            'status' => StatusPneuEnum::EM_USO,
            'local' => LocalPneuEnum::FROTA,
        ]);

        Log::info(__METHOD__.' - Pneus após aplicação.', [
            'pneu_veiculo' => $pneuVeiculo,
            'pneu' => $pneuVeiculo->pneu,
        ]);
    }

    public function trocarPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {

        DB::transaction(function () use ($pneuVeiculo, $data) {
            $this->removerPneu($pneuVeiculo, [
                'data_final' => $data['data_movimento'],
                'km_final' => $data['km_movimento'],
                'sulco' => $data['sulco'] ?? 0,
                'motivo' => $data['motivo'],
                'observacao' => $data['observacao'] ?? null,
                'anexos' => $data['anexos'] ?? null,
            ]);

            $this->aplicarPneu($pneuVeiculo, [
                'pneu_id' => $data['pneu_id'],
                'data_inicial' => $data['data_movimento'],
                'km_inicial' => $data['km_movimento'],
                'motivo' => $data['motivo'],
                'sulco' => $data['sulco'] ?? 0,
                'observacao' => $data['observacao'] ?? null,
                'anexos' => $data['anexos'] ?? null,
            ]);
        }, 3);
    }

    public function rodizioPneu(Collection $pneusVeiculo, array $data)
    {
        DB::transaction(function () use ($pneusVeiculo, $data) {
            if ($pneusVeiculo->count() !== 2) {
                return;
            }

            $pneusId = $pneusVeiculo->pluck('pneu_id')->toArray();

            $pneusVeiculo->each(function (PneuPosicaoVeiculo $pneuVeiculo) use ($pneusId, $data) {

                $pneuId = Arr::where($pneusId, function ($id) use ($pneuVeiculo) {
                    return $id !== $pneuVeiculo->pneu_id;
                });

                $pneuId = Arr::first($pneuId);

                $this->removerPneu($pneuVeiculo, [
                    'data_final' => $data['data_movimento'],
                    'km_final' => $data['km_movimento'],
                    'sulco' => $data['sulco'] ?? 0,
                    'motivo' => MotivoMovimentoPneuEnum::RODIZIO->value,
                    'observacao' => $data['observacao'] ?? null,
                    'anexos' => $data['anexos'] ?? null,
                ]);

                $this->aplicarPneu($pneuVeiculo, [
                    'pneu_id' => $pneuId,
                    'data_inicial' => $data['data_movimento'],
                    'km_inicial' => $data['km_movimento'],
                    'motivo' => MotivoMovimentoPneuEnum::RODIZIO,
                    'sulco' => $data['sulco'] ?? 0,
                    'observacao' => $data['observacao'] ?? null,
                    'anexos' => $data['anexos'] ?? null,
                ]);
            });
        }, 3);
    }

    public function reaplicarPneu(PneuPosicaoVeiculo $origem, PneuPosicaoVeiculo $destino, array $data)
    {
        DB::transaction(function () use ($origem, $destino, $data) {
            if (blank($origem->pneu_id)) {
                throw new \DomainException('A posição de origem não possui pneu aplicado.');
            }

            if (filled($destino->pneu_id)) {
                throw new \DomainException('A posição de destino precisa estar vazia para reaplicar o pneu.');
            }

            $pneuId = $origem->pneu_id;

            $this->removerPneu($origem, [
                'data_final' => $data['data_movimento'],
                'km_final' => $data['km_movimento'],
                'sulco' => $data['sulco'] ?? 0,
                'motivo' => MotivoMovimentoPneuEnum::REAPLICACAO->value,
                'observacao' => $data['observacao'] ?? null,
                'anexos' => $data['anexos'] ?? null,
            ]);

            $this->aplicarPneu($destino, [
                'pneu_id' => $pneuId,
                'data_inicial' => $data['data_movimento'],
                'km_inicial' => $data['km_movimento'],
                'motivo' => MotivoMovimentoPneuEnum::REAPLICACAO,
                'sulco' => $data['sulco'] ?? 0,
                'observacao' => $data['observacao'] ?? null,
                'anexos' => $data['anexos'] ?? null,
            ]);
        }, 3);
    }

    public function registrarHistoricoMovimento(array $data): ?Models\HistoricoMovimentoPneu
    {
        try {

            $action = new Actions\CreateHistoricoMovimentoPneu;
            $historico = $action->handle($data);

            if ($action->hasError) {
                $this->setError($action->message, $action->errors);

                return null;
            }

            return $historico;
        } catch (\Exception $e) {
            Log::error(__METHOD__.' - Erro ao registrar histórico de movimento.', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function registrarInspecaoMovimentacao(PneuPosicaoVeiculo $pneuVeiculo, array $data): Models\PneuInspecao
    {
        $sulco = $data['sulco'] ?? 0;

        return Models\PneuInspecao::query()->create([
            'pneu_id' => $pneuVeiculo->pneu_id,
            'pneu_ciclo_id' => $pneuVeiculo->pneu_ciclo_id ?: $this->cicloService->getCurrentCycle($pneuVeiculo->pneu)?->id,
            'veiculo_id' => $pneuVeiculo->veiculo_id,
            'pneu_posicao_veiculo_id' => $pneuVeiculo->id,
            'tipo' => TipoInspecaoPneuEnum::MOVIMENTACAO,
            'resultado' => ResultadoInspecaoPneuEnum::APROVADO,
            'data_inspecao' => $data['data_final'],
            'km_referencia' => $data['km_final'],
            'sulco_interno' => $sulco,
            'sulco_centro' => $sulco,
            'sulco_externo' => $sulco,
            'observacao' => $data['observacao'] ?? null,
            'anexos' => $data['anexos'] ?? null,
        ]);
    }
}
