<?php

namespace App\Filament\Widgets;

use App\Models\ManutencaoLancamento;
use App\Support\Filters\ParsesDateRangeFilter;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OficinaManutencaoResumo extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 1;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        [$start, $end] = $this->parseDateRangeFilter($this->pageFilters['data_negociacao'] ?? null);

        $query = ManutencaoLancamento::query()
            ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]));

        $totalCentavos = (int) $query->sum('valor_total_centavos');
        $veiculos = (int) $query->distinct('veiculo_id')->count('veiculo_id');
        $grupos = (int) $query->distinct('grupo_produto')->count('grupo_produto');

        $mediaVeiculo = $veiculos > 0 ? $totalCentavos / $veiculos : 0;

        return [
            Stat::make('Custo total', 'R$ '.number_format($totalCentavos / 100, 2, ',', '.'))
                ->description($grupos.' grupos de produto no período')
                ->descriptionIcon(Heroicon::Banknotes, IconPosition::Before)
                ->color('success'),
            Stat::make('Custo médio por veículo', 'R$ '.number_format($mediaVeiculo / 100, 2, ',', '.'))
                ->description($veiculos.' veículos no período')
                ->descriptionIcon(Heroicon::Truck, IconPosition::Before)
                ->color('info'),
        ];
    }
}
