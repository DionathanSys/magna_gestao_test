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
        $totalLancamentos = (int) $query->count();
        $veiculos = (int) $query->distinct('veiculo_id')->count('veiculo_id');
        $grupos = (int) $query->distinct('grupo_produto')->count('grupo_produto');

        $ticketMedio = $totalLancamentos > 0 ? $totalCentavos / $totalLancamentos : 0;
        $mediaVeiculo = $veiculos > 0 ? $totalCentavos / $veiculos : 0;

        return [
            Stat::make('Custo total', 'R$ '.number_format($totalCentavos / 100, 2, ',', '.'))
                ->description('Ticket médio R$ '.number_format($ticketMedio / 100, 2, ',', '.'))
                ->descriptionIcon(Heroicon::Banknotes, IconPosition::Before)
                ->color('success'),
            Stat::make('Veículos impactados', (string) $veiculos)
                ->description('Média/veículo R$ '.number_format($mediaVeiculo / 100, 2, ',', '.'))
                ->descriptionIcon(Heroicon::Truck, IconPosition::Before)
                ->color('info'),
            Stat::make('Lançamentos', (string) $totalLancamentos)
                ->description($grupos.' grupos de produto no período')
                ->descriptionIcon(Heroicon::WrenchScrewdriver, IconPosition::Before)
                ->color('warning'),
        ];
    }
}
