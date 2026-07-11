<?php

namespace App\Filament\Widgets;

use App\Models\ManutencaoLancamento;
use App\Support\Filters\ParsesDateRangeFilter;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OficinaManutencaoComparativoPeriodo extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ParsesDateRangeFilter;

    protected static ?int $sort = 2;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        [$currentStart, $currentEnd] = $this->resolveCurrentPeriod();

        if (! $currentStart || ! $currentEnd) {
            return [
                Stat::make('Comparativo de Período', 'Sem dados')
                    ->description('Não há lançamentos suficientes para comparar períodos.')
                    ->descriptionIcon(Heroicon::CalendarDays, IconPosition::Before)
                    ->color('gray'),
            ];
        }

        $periodDays = $currentStart->copy()->startOfDay()->diffInDays($currentEnd->copy()->startOfDay()) + 1;
        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        $currentTotal = $this->sumPeriod($currentStart, $currentEnd);
        $previousTotal = $this->sumPeriod($previousStart, $previousEnd);

        $difference = $currentTotal - $previousTotal;
        $differencePercent = $previousTotal > 0
            ? ($difference / $previousTotal) * 100
            : ($currentTotal > 0 ? 100 : 0);

        $description = sprintf(
            '%s x %s | %s',
            $this->formatPeriod($currentStart, $currentEnd),
            $this->formatPeriod($previousStart, $previousEnd),
            $this->formatDifference($difference, $differencePercent)
        );

        return [
            Stat::make('Atual x Período Anterior', 'R$ '.number_format($currentTotal / 100, 2, ',', '.'))
                ->description($description)
                ->descriptionIcon($difference <= 0 ? Heroicon::ArrowTrendingDown : Heroicon::ArrowTrendingUp, IconPosition::Before)
                ->color($difference <= 0 ? 'success' : 'danger'),
        ];
    }

    private function resolveCurrentPeriod(): array
    {
        [$start, $end] = $this->parseDateRangeFilter($this->pageFilters['data_negociacao'] ?? null);

        if ($start && $end) {
            return [$start->copy()->startOfDay(), $end->copy()->endOfDay()];
        }

        $latestDate = ManutencaoLancamento::query()->max('data_negociacao');

        if (! $latestDate) {
            return [null, null];
        }

        $end = Carbon::parse($latestDate)->endOfDay();
        $start = Carbon::parse($latestDate)->startOfMonth()->startOfDay();

        return [$start, $end];
    }

    private function sumPeriod(Carbon $start, Carbon $end): int
    {
        return (int) ManutencaoLancamento::query()
            ->whereBetween('data_negociacao', [$start->toDateString(), $end->toDateString()])
            ->sum('valor_total_centavos');
    }

    private function formatPeriod(Carbon $start, Carbon $end): string
    {
        return $start->format('d/m').' - '.$end->format('d/m');
    }

    private function formatDifference(int $difference, float $differencePercent): string
    {
        $prefix = $difference > 0 ? '+' : '';

        return sprintf(
            '%sR$ %s (%s%s%%)',
            $prefix,
            number_format($difference / 100, 2, ',', '.'),
            $prefix,
            number_format($differencePercent, 2, ',', '.')
        );
    }
}
