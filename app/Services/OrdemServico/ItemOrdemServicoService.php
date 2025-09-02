<?php

namespace App\Services\OrdemServico;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use App\Models\ItemOrdemServico;
use App\Models\OrdemServico;
use App\Models\PlanoManutencaoOrdemServico;
use App\Services\NotificacaoService as notify;

class ItemOrdemServicoService
{
    public static function create(array $data)
    {
        ItemOrdemServico::query()->updateOrCreate(
            [
                'ordem_servico_id'  => $data['ordem_servico_id'],
                'servico_id'        => $data['servico_id'],
            ],
            [
                'ordem_servico_id'  => $data['ordem_servico_id'],
                'servico_id'        => $data['servico_id'],
                'plano_preventivo_id' => $data['plano_preventivo_id'] ?? null,
                'posicao'           => $data['posicao'] ?? null,
                'observacao'        => $data['observacao'] ?? null,
                'status'            => $data['status'] ?? null,
                'created_by'        => $data['created_by'] ?? null,
            ]
        );
    }

    public static function removerItensComPlanoPreventivo(PlanoManutencaoOrdemServico $planoManutencaoOrdemServico): bool
    {
        $itens = ItemOrdemServico::query()
            ->where('ordem_servico_id', $planoManutencaoOrdemServico->ordem_servico_id)
            ->where('plano_preventivo_id', $planoManutencaoOrdemServico->plano_preventivo_id)
            ->get();

        if ($itens->where('status', '!=', StatusOrdemServicoEnum::PENDENTE)->count() > 0) {
            notify::error('Não é possível remover o vínculo com o plano preventivo, pois existem itens com status diferente de PENDENTE.');
            return false;
        }

        $itens->each(function ($item) {
            $item->delete();
        });

        return true;
    }

    public static function delete(ItemOrdemServico $itemOrdemServico)
    {
        if ($itemOrdemServico->status != StatusOrdemServicoEnum::PENDENTE) {
            notify::error('Não é possível remover um item de ordem de serviço que não esteja pendente.');
            return;
        }

        if($itemOrdemServico->plano_preventivo_id) {
            notify::alert('Não é possível remover um item de ordem de serviço que esteja associado a um plano preventivo.');
            return;
        }

        $itemOrdemServico->delete();

        notify::success('Item de Ordem de Serviço removido com sucesso.');
    }

}
