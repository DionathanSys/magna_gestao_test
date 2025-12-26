<?php

namespace App\Filament\Resources\ResultadoPeriodos\Widgets;

use App\Filament\Resources\ResultadoPeriodos\Pages\ListResultadoPeriodos;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResultadoPeriodoStats extends StatsOverviewWidget
{

    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'lg' => 3,
            'xl' => 5,
        ];
    }

    protected function getTablePage(): string
    {
        return ListResultadoPeriodos::class;
    }

    protected function getStats(): array
    {
        $records = $this->getPageTableQuery()->get();

        $registrosCount = $records->count();
        $faturamento = $records->sum('documentos_sum_valor_liquido') / 100;
        $manutencao = $records->sum('manutencao_sum_custo_total') / 100;
        $manutencaoMedia = $manutencao > 0 ? $manutencao / $registrosCount : 0;
        $percentualManutencaoFaturamento = $manutencao > 0 ? $manutencao / $faturamento : 0;
        $combustivel = $records->sum('abastecimentos_sum_preco_total') / 100;
        $combustivelMedio = $combustivel > 0 ? $combustivel / $registrosCount : 0;
        $percentualCombustivelFaturamento = $combustivel > 0 ? $combustivel / $faturamento : 0;

        return [
            Stat::make('Faturamento','R$ ' .  number_format($faturamento, 2, ',', '.'))
                ->description($registrosCount . ' Registros')
                ->descriptionIcon(Heroicon::ChartBar, IconPosition::Before)
                ->color('success'),
            Stat::make('Combustível','R$ ' .  number_format($combustivel, 2, ',', '.') . '-' . number_format($percentualCombustivelFaturamento, 2, ',', '.') . '%')
                ->description('Combustível/Veículo R$ '. number_format($combustivelMedio, 2, ',', '.'))
                ->descriptionIcon(Heroicon::ChartBar, IconPosition::Before)
                ->color('success'),
            Stat::make('Manutenção','R$ ' .  number_format($manutencao, 2, ',', '.') . '-' . number_format($percentualManutencaoFaturamento, 2, ',', '.') . '%')
                ->description('Manutenção/Veículo R$ '. $manutencaoMedia)
                ->descriptionIcon(Heroicon::ChartBar, IconPosition::Before)
                ->color('success'),
        ];
    }
}
