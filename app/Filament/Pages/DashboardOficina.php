<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OficinaManutencaoPorGrupoProduto;
use App\Filament\Widgets\OficinaManutencaoPorVeiculo;
use App\Filament\Widgets\OficinaManutencaoEvolucaoMensal;
use App\Filament\Widgets\OficinaManutencaoItensRecorrentes;
use App\Filament\Widgets\OficinaManutencaoComparativoPeriodo;
use App\Filament\Widgets\OficinaManutencaoResumo;
use App\Filament\Widgets\OficinaManutencaoTipoResumo;
use BackedEnum;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Page;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use UnitEnum;

class DashboardOficina extends Page
{
    use HasFiltersAction;

    protected string $view = 'filament.pages.dashboard-oficina';

    protected static ?string $title = 'Dashboard Oficina';

    protected static ?string $navigationLabel = 'Dashboard Oficina';

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    DateRangePicker::make('data_negociacao')
                        ->label('Período de negociação')
                        ->autoApply()
                        ->firstDayOfWeek(0)
                        ->alwaysShowCalendar(),
                ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OficinaManutencaoResumo::class,
            OficinaManutencaoComparativoPeriodo::class,
            OficinaManutencaoTipoResumo::class,
            OficinaManutencaoPorVeiculo::class,
            OficinaManutencaoPorGrupoProduto::class,
            OficinaManutencaoEvolucaoMensal::class,
            OficinaManutencaoItensRecorrentes::class,
        ];
    }
}
