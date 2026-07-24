<?php

namespace App\Services\Garantia;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models\GarantiaServico;
use App\Models\ItemOrdemServico;
use App\Models\OrdemServico;
use Illuminate\Support\Collection;

class GarantiaServicoService
{
    public function registrarOrdemServico(OrdemServico $ordemServico): Collection
    {
        $ordemServico->loadMissing(['itens.servico']);

        return $ordemServico->itens
            ->filter(fn (ItemOrdemServico $item): bool => $item->status === StatusOrdemServicoEnum::CONCLUIDO)
            ->map(fn (ItemOrdemServico $item): GarantiaServico => $this->registrarItem($item, $ordemServico));
    }

    public function registrarItem(ItemOrdemServico $item, OrdemServico $ordemServico): GarantiaServico
    {
        $servico = $item->servico;
        $controlaPosicao = (bool) $servico?->controla_posicao;
        $posicao = $controlaPosicao ? $item->posicao : null;
        $dataExecucao = $ordemServico->data_fim ?? now();
        $kmExecucao = (int) $ordemServico->quilometragem;
        $garantiaKm = (int) ($servico?->garantia_km ?: db_config('config-garantia.garantia_km_default', 10000));
        $garantiaDias = (int) ($servico?->garantia_dias ?: db_config('config-garantia.garantia_dias_default', 90));
        $garantiaKm = max(1, $garantiaKm);
        $garantiaDias = max(1, $garantiaDias);

        $anterior = $this->buscarExecucaoAnterior($ordemServico, $item, $controlaPosicao, $posicao);
        $kmDurabilidade = $anterior ? max(0, $kmExecucao - (int) $anterior->km_execucao) : null;
        $diasDurabilidade = $anterior ? (int) $anterior->data_execucao->diffInDays($dataExecucao) : null;
        $emGarantia = $anterior !== null
            && $kmDurabilidade <= $garantiaKm
            && $diasDurabilidade <= $garantiaDias;

        return GarantiaServico::query()->updateOrCreate(
            ['item_ordem_servico_id' => $item->id],
            [
                'item_ordem_servico_anterior_id' => $anterior?->item_ordem_servico_id,
                'ordem_servico_id' => $ordemServico->id,
                'ordem_servico_anterior_id' => $anterior?->ordem_servico_id,
                'veiculo_id' => $ordemServico->veiculo_id,
                'servico_id' => $item->servico_id,
                'controla_posicao' => $controlaPosicao,
                'posicao' => $posicao,
                'km_execucao' => $kmExecucao,
                'data_execucao' => $dataExecucao,
                'km_execucao_anterior' => $anterior?->km_execucao,
                'data_execucao_anterior' => $anterior?->data_execucao,
                'km_durabilidade' => $kmDurabilidade,
                'dias_durabilidade' => $diasDurabilidade,
                'garantia_km_aplicada' => $garantiaKm,
                'garantia_dias_aplicada' => $garantiaDias,
                'em_garantia' => $emGarantia,
                'motivo_alerta' => $emGarantia ? $this->motivoAlerta($kmDurabilidade, $diasDurabilidade, $garantiaKm, $garantiaDias) : null,
            ]
        );
    }

    public function alertasDaOrdem(OrdemServico|int $ordemServico): Collection
    {
        $ordemServicoId = $ordemServico instanceof OrdemServico ? $ordemServico->id : $ordemServico;

        return GarantiaServico::query()
            ->with(['servico:id,codigo,descricao', 'veiculo:id,placa'])
            ->where('ordem_servico_id', $ordemServicoId)
            ->where('em_garantia', true)
            ->get();
    }

    protected function buscarExecucaoAnterior(OrdemServico $ordemServico, ItemOrdemServico $item, bool $controlaPosicao, ?string $posicao): ?GarantiaServico
    {
        return GarantiaServico::query()
            ->where('veiculo_id', $ordemServico->veiculo_id)
            ->where('servico_id', $item->servico_id)
            ->where('ordem_servico_id', '!=', $ordemServico->id)
            ->when(
                $controlaPosicao,
                fn ($query) => $query->where('posicao', $posicao),
                fn ($query) => $query->whereNull('posicao')
            )
            ->orderByDesc('data_execucao')
            ->orderByDesc('id')
            ->first();
    }

    protected function motivoAlerta(?int $kmDurabilidade, ?int $diasDurabilidade, int $garantiaKm, int $garantiaDias): string
    {
        return sprintf(
            'Retorno dentro da garantia: %s km/%s dias de durabilidade. Limite: %s km/%s dias.',
            number_format($kmDurabilidade ?? 0, 0, ',', '.'),
            number_format($diasDurabilidade ?? 0, 0, ',', '.'),
            number_format($garantiaKm, 0, ',', '.'),
            number_format($garantiaDias, 0, ',', '.')
        );
    }
}
