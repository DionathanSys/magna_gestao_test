<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DispersaoMedia extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $veiculoId = $this->pageFilters['veiculo_id'] ?? null;
        $dataCompetencia = $this->pageFilters['data_competencia'] ?? null;

        $viagens = \App\Models\Viagem::query()
            ->when($veiculoId, function ($query, $veiculoId) {
                return $query->where('veiculo_id', $veiculoId);
            })
            ->when($dataCompetencia, function ($query, $dataCompetencia) {
                // data_competencia pode chegar como array ou como string no formato
                // 'dd/mm/YYYY - dd/mm/YYYY'. Tratamos ambos os casos.
                try {
                    if (is_array($dataCompetencia) && count($dataCompetencia) === 2) {
                        return $query->whereBetween('data_competencia', [
                            $dataCompetencia[0],
                            $dataCompetencia[1]
                        ]);
                    }

                    if (is_string($dataCompetencia) && str_contains($dataCompetencia, ' - ')) {
                        [$start, $end] = array_map('trim', explode(' - ', $dataCompetencia, 2));

                        // converte 'dd/mm/YYYY' -> 'YYYY-mm-dd'
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');

                        return $query->whereBetween('data_competencia', [$startDate, $endDate]);
                    }
                } catch (\Throwable $e) {
                    // se o parse falhar, ignoramos o filtro de data e continuamos
                }

                return $query;
            })
            ->where('considerar_relatorio', true)
            ->get();

        $countViagensNaoConsideradas = \App\Models\Viagem::query()
            ->when($veiculoId, function ($query, $veiculoId) {
                return $query->where('veiculo_id', $veiculoId);
            })
            ->when($dataCompetencia, function ($query, $dataCompetencia) {
                // data_competencia pode chegar como array ou como string no formato
                // 'dd/mm/YYYY - dd/mm/YYYY'. Tratamos ambos os casos.
                try {
                    if (is_array($dataCompetencia) && count($dataCompetencia) === 2) {
                        return $query->whereBetween('data_competencia', [
                            $dataCompetencia[0],
                            $dataCompetencia[1]
                        ]);
                    }

                    if (is_string($dataCompetencia) && str_contains($dataCompetencia, ' - ')) {
                        [$start, $end] = array_map('trim', explode(' - ', $dataCompetencia, 2));

                        // converte 'dd/mm/YYYY' -> 'YYYY-mm-dd'
                        $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');

                        return $query->whereBetween('data_competencia', [$startDate, $endDate]);
                    }
                } catch (\Throwable $e) {
                    // se o parse falhar, ignoramos o filtro de data e continuamos
                }

                return $query;
            })
            ->where('considerar_relatorio', false)
            ->count();

        $totalViagens = $viagens->count();
        $totalKmRodado = $viagens->sum('km_rodado');
        $totalKmPago = $viagens->sum('km_pago');
        $kmDispersao = round($totalKmRodado - $totalKmPago, 2);

        $dispersaoMedia = $totalKmPago > 0
            ? ($totalKmRodado - $totalKmPago) / $totalKmPago * 100
            : 0;

        $dispersaoPorViagem = $totalViagens > 0
            ? $kmDispersao / $totalViagens
            : 0;

        return [
            Stat::make('Dispersão geral', number_format($kmDispersao, 0, ',', '.') . ' km - ' . number_format($dispersaoMedia, 2, ',', '.') . '%')
                ->description(number_format($dispersaoPorViagem, 2, ',', '.') . ' Km/Viagem'),
            Stat::make('Total km rodado', number_format($totalKmRodado, 0, ',', '.') . ' km')
                ->description(($totalViagens > 0 ? number_format($totalKmRodado / $totalViagens, 2, ',', '.') . ' Km/Viagem' : '0 Km/Viagem')),
            Stat::make('Qtde viagens', $totalViagens)
                ->description($countViagensNaoConsideradas > 0 ? $countViagensNaoConsideradas . ' viagens desconsideradas' : 'Não houve viagens desconsideradas'),
        ];
    }
}
