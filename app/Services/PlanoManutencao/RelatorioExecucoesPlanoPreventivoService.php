<?php

namespace App\Services\PlanoManutencao;

use App\Models\PlanoManutencaoOrdemServico;
use BackedEnum;

class RelatorioExecucoesPlanoPreventivoService
{
    /**
     * @return array<int, array<string, int|float|string|null>>
     */
    public function obterDados(array $filtros = []): array
    {
        if (blank($filtros['veiculo_id'] ?? null)) {
            return [];
        }

        $execucoes = PlanoManutencaoOrdemServico::query()
            ->with([
                'veiculo:id,placa',
                'planoPreventivo:id,descricao,intervalo',
                'ordemServico:id,status',
            ])
            ->where('veiculo_id', $filtros['veiculo_id'])
            ->when(
                filled($filtros['plano_preventivo_id'] ?? null),
                fn ($query) => $query->where('plano_preventivo_id', $filtros['plano_preventivo_id'])
            )
            ->when(
                filled($filtros['data_de'] ?? null),
                fn ($query) => $query->whereDate('data_execucao', '>=', $filtros['data_de'])
            )
            ->when(
                filled($filtros['data_ate'] ?? null),
                fn ($query) => $query->whereDate('data_execucao', '<=', $filtros['data_ate'])
            )
            ->orderByDesc('data_execucao')
            ->orderByDesc('km_execucao')
            ->orderByDesc('id')
            ->get();

        return $execucoes
            ->map(function (PlanoManutencaoOrdemServico $execucao): array {
                $status = $execucao->ordemServico?->status;
                $status = $status instanceof BackedEnum ? $status->value : $status;

                return [
                    'id' => (int) $execucao->id,
                    'veiculo_id' => (int) $execucao->veiculo_id,
                    'placa' => $execucao->veiculo?->placa,
                    'plano_preventivo_id' => (int) $execucao->plano_preventivo_id,
                    'plano_descricao' => $execucao->planoPreventivo?->descricao,
                    'intervalo' => $execucao->planoPreventivo?->intervalo !== null
                        ? (float) $execucao->planoPreventivo->intervalo
                        : null,
                    'km_execucao' => (float) $execucao->km_execucao,
                    'data_execucao' => $execucao->data_execucao?->format('Y-m-d'),
                    'ordem_servico_id' => $execucao->ordem_servico_id
                        ? (int) $execucao->ordem_servico_id
                        : null,
                    'ordem_servico_status' => $status,
                    'origem' => $execucao->ordem_servico_id ? 'Ordem de serviço' : 'Registro manual',
                ];
            })
            ->all();
    }
}
