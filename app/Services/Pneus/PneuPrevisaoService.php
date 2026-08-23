<?php

namespace App\Services\Pneus;

use App\Models\PneuInspecao;
use App\Models\PneuPosicaoVeiculo;
use Illuminate\Support\Collection;

class PneuPrevisaoService
{
    public function getProgramacao(Collection $posicoes): Collection
    {
        $antecedenciaKm = (int) db_config('config-pneu.antecedencia_previsao_km', 1000);
        $limiteTroca = (float) db_config('config-pneu.limite_sulco_troca_mm', 3);
        $limiteRodizio = (int) db_config('config-pneu.alerta_km_rodizio', 7000);
        $inspecoesPorCiclo = $this->getInspecoesPorCiclo($posicoes);

        return $posicoes
            ->flatMap(function (PneuPosicaoVeiculo $posicao) use ($antecedenciaKm, $inspecoesPorCiclo, $limiteRodizio, $limiteTroca): array {
                $programacao = [];
                $kmRestanteRodizio = $limiteRodizio - (int) $posicao->km_rodado;

                if ($kmRestanteRodizio > 0 && $kmRestanteRodizio <= $antecedenciaKm) {
                    $programacao[] = $this->buildItem(
                        $posicao,
                        'rodizio',
                        $kmRestanteRodizio,
                        null,
                        null,
                        'warning',
                        'Programar rodízio'
                    );
                }

                $inspecoes = $inspecoesPorCiclo->get($posicao->pneu_ciclo_id, collect());
                $previsaoTroca = $this->calcularPrevisaoTroca($inspecoes, $limiteTroca);

                if ($previsaoTroca !== null && $previsaoTroca['km_restante'] <= $antecedenciaKm) {
                    $programacao[] = $this->buildItem(
                        $posicao,
                        'troca',
                        $previsaoTroca['km_restante'],
                        $previsaoTroca['sulco_atual'],
                        $previsaoTroca['desgaste_por_mil_km'],
                        $previsaoTroca['km_restante'] <= 0 ? 'danger' : 'warning',
                        $previsaoTroca['km_restante'] <= 0
                            ? 'Troca necessária'
                            : 'Programar troca'
                    );
                }

                return $programacao;
            })
            ->sortBy([
                [fn (array $item) => $item['severidade'] === 'danger' ? 0 : 1, 'asc'],
                ['km_restante', 'asc'],
                ['placa', 'asc'],
            ])
            ->values();
    }

    public function getTotalSemDados(Collection $posicoes): int
    {
        $inspecoesPorCiclo = $this->getInspecoesPorCiclo($posicoes);

        return $posicoes
            ->filter(fn (PneuPosicaoVeiculo $posicao) => $inspecoesPorCiclo->get($posicao->pneu_ciclo_id, collect())->count() < 2)
            ->count();
    }

    private function getInspecoesPorCiclo(Collection $posicoes): Collection
    {
        $ciclosIds = $posicoes->pluck('pneu_ciclo_id')->filter()->unique();

        if ($ciclosIds->isEmpty()) {
            return collect();
        }

        return PneuInspecao::query()
            ->whereIn('pneu_ciclo_id', $ciclosIds)
            ->whereNotNull('km_referencia')
            ->whereNotNull('sulco_interno')
            ->orderBy('km_referencia')
            ->orderBy('id')
            ->get()
            ->map(fn (PneuInspecao $inspecao) => [
                'pneu_ciclo_id' => $inspecao->pneu_ciclo_id,
                'km' => (int) $inspecao->km_referencia,
                'sulco' => min([
                    (float) $inspecao->sulco_interno,
                    (float) ($inspecao->sulco_centro ?? $inspecao->sulco_interno),
                    (float) ($inspecao->sulco_externo ?? $inspecao->sulco_interno),
                ]),
            ])
            ->groupBy('pneu_ciclo_id');
    }

    private function calcularPrevisaoTroca(Collection $inspecoes, float $limiteTroca): ?array
    {
        $medicoes = $inspecoes->filter(fn (array $inspecao) => isset($inspecao['km'], $inspecao['sulco']))->values();

        if ($medicoes->count() < 2) {
            return null;
        }

        $anterior = $medicoes->get($medicoes->count() - 2);
        $atual = $medicoes->last();
        $kmPercorrido = $atual['km'] - $anterior['km'];
        $desgaste = $anterior['sulco'] - $atual['sulco'];

        if ($kmPercorrido <= 0 || $desgaste <= 0) {
            return null;
        }

        $desgastePorKm = $desgaste / $kmPercorrido;

        return [
            'km_restante' => (int) floor(($atual['sulco'] - $limiteTroca) / $desgastePorKm),
            'sulco_atual' => $atual['sulco'],
            'desgaste_por_mil_km' => $desgastePorKm * 1000,
        ];
    }

    private function buildItem(
        PneuPosicaoVeiculo $posicao,
        string $tipo,
        int $kmRestante,
        ?float $sulcoAtual,
        ?float $desgastePorMilKm,
        string $severidade,
        string $titulo,
    ): array {
        $dataPrevista = $posicao->veiculo?->calcularDataPrevista(max(0, $kmRestante));

        return [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'severidade' => $severidade,
            'placa' => $posicao->veiculo?->placa ?? 'N/A',
            'veiculo_id' => $posicao->veiculo_id,
            'posicao' => $posicao->mapaPosicao?->codigo ?? $posicao->posicao,
            'numero_fogo' => $posicao->pneu?->numero_fogo ?? 'N/A',
            'km_restante' => $kmRestante,
            'data_prevista' => $dataPrevista?->format('d/m/Y'),
            'sulco_atual' => $sulcoAtual,
            'desgaste_por_mil_km' => $desgastePorMilKm,
        ];
    }
}
