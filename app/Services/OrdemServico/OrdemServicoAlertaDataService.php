<?php

namespace App\Services\OrdemServico;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models\Agendamento;
use App\Models\OrdemServico;
use App\Services\PlanoManutencao\RelatorioPlanoManutencaoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class OrdemServicoAlertaDataService
{
    public const KM_BASE_RELATORIO_MANUTENCAO = 5000;

    public function agendamentosPendentes(OrdemServico $ordemServico): Collection
    {
        if ($ordemServico->relationLoaded('agendamentosPendentes')) {
            return $ordemServico->getRelation('agendamentosPendentes');
        }

        return Agendamento::query()
            ->with([
                'servico:id,descricao',
                'parceiro:id,nome',
            ])
            ->where('veiculo_id', $ordemServico->veiculo_id)
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->get();
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function planosPreventivos(OrdemServico $ordemServico): SupportCollection
    {
        return collect(app(RelatorioPlanoManutencaoService::class)->obterDadosRelatorio([
            'veiculo_id' => $ordemServico->veiculo_id,
            'km_restante_maximo' => self::KM_BASE_RELATORIO_MANUTENCAO,
        ]));
    }
}
