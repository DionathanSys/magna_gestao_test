<?php

namespace App\Services\OrdemServico;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models\OrdemServico;
use App\Models\PlanoManutencaoOrdemServico;
use App\Models\PlanoPreventivo;
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Facades\Auth;

class ManutencaoPreventivaService
{
    public static function associarPlanoPreventivo(OrdemServico $ordemServico, $planoPreventivoId)
    {
        $manutencaoPreventivaAssociada = PlanoManutencaoOrdemServico::query()
            ->where('ordem_servico_id', $ordemServico->id)
            ->where('plano_preventivo_id', $planoPreventivoId)
            ->first();

        if ($manutencaoPreventivaAssociada) {
            notify::error('Plano Preventivo já associado a esta Ordem de Serviço.');
            return;
        }

        $manutencaoPreventivaAssociada = PlanoManutencaoOrdemServico::create([
            'plano_preventivo_id'   => $planoPreventivoId,
            'ordem_servico_id'      => $ordemServico->id,
            'veiculo_id'            => $ordemServico->veiculo_id,
            'km_execucao'           => $ordemServico->quilometragem,
            'data_execucao'         => $ordemServico->data_inicio,
        ]);

        $itensPlano = $manutencaoPreventivaAssociada->planoPreventivo->itens;

        try {
            foreach ($itensPlano as $item) {
                ItemOrdemServicoService::create([
                    'ordem_servico_id'  => $ordemServico->id,
                    'servico_id'        => $item['servico_id'],
                    'plano_preventivo_id' => $planoPreventivoId,
                    'posicao'           => null,
                    'observacao'        => null,
                    'status'            => StatusOrdemServicoEnum::PENDENTE,
                    'created_by'        => Auth::user()->id,
                ]);
            }
        } catch (\Exception $e) {
            $manutencaoPreventivaAssociada->delete();
            notify::error('Erro ao associar Plano Preventivo. Itens não foram criados');
            return;
        }

        if ($manutencaoPreventivaAssociada) {
            notify::success('Plano Preventivo associado com sucesso.');
        } else {
            notify::error('Erro ao associar Plano Preventivo.');
        }
    }

    public static function desassociarPlanoPreventivo(PlanoManutencaoOrdemServico $planoManutencaoOrdemServico)
    {

        if (! ItemOrdemServicoService::removerItensComPlanoPreventivo($planoManutencaoOrdemServico)){
            notify::error('Erro ao desassociar Plano Preventivo.');
            return;
        }

        $planoManutencaoOrdemServico->delete();
        notify::success('Plano Preventivo desassociado com sucesso.');
    }
}
