<?php

namespace App\Filament\Widgets;

use App\Models\ManutencaoLancamento;
use App\Support\Filters\ParsesDateRangeFilter;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OficinaManutencaoTipoResumo extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 3;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        [$start, $end] = $this->parseDateRangeFilter($this->pageFilters['data_negociacao'] ?? null);

        $query = ManutencaoLancamento::query()
            ->when($start && $end, fn ($query) => $query->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()]));

        $totalCentavos = (int) (clone $query)->sum('valor_total_centavos');
        $preventivaCentavos = (int) (clone $query)->where('tipo_manutencao', 'Preventiva')->sum('valor_total_centavos');
        $corretivaCentavos = (int) (clone $query)->where('tipo_manutencao', 'Corretiva')->sum('valor_total_centavos');
        $preventivaCount = (int) (clone $query)->where('tipo_manutencao', 'Preventiva')->count();
        $corretivaCount = (int) (clone $query)->where('tipo_manutencao', 'Corretiva')->count();

        $preventivaPercentual = $totalCentavos > 0 ? ($preventivaCentavos / $totalCentavos) * 100 : 0;
        $corretivaPercentual = $totalCentavos > 0 ? ($corretivaCentavos / $totalCentavos) * 100 : 0;

        return [
            Stat::make('Preventiva', 'R$ '.number_format($preventivaCentavos / 100, 2, ',', '.').' - '.number_format($preventivaPercentual, 2, ',', '.').'%')
                ->description($preventivaCount.' lançamentos preventivos')
                ->descriptionIcon(Heroicon::ShieldCheck, IconPosition::Before)
                ->color('success'),
            Stat::make('Corretiva', 'R$ '.number_format($corretivaCentavos / 100, 2, ',', '.').' - '.number_format($corretivaPercentual, 2, ',', '.').'%')
                ->description($corretivaCount.' lançamentos corretivos')
                ->descriptionIcon(Heroicon::ExclamationTriangle, IconPosition::Before)
                ->color($corretivaCentavos > $preventivaCentavos ? 'danger' : 'warning'),
        ];
    }
}
