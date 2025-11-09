<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DispersaoIntegrado;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use UnitEnum;

class KpiViagem extends Page
{
    use HasFiltersAction;

    protected string $view = 'filament.pages.kpi-viagem';

    protected static ?string $title = 'Dashboard Viagens';

    protected static ?string $navigationLabel = 'Dashboard Viagens';

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    Select::make('veiculo_id')
                        ->label('Placa')
                        ->options(fn() => \App\Models\Veiculo::query()
                            ->where('is_active', true)
                            ->pluck('placa', 'id'))
                        ->searchable()
                        ->placeholder('Selecione uma placa'),
                    DateRangePicker::make('data_competencia')
                        ->label('Dt. Competência')
                        ->autoApply()
                        ->firstDayOfWeek(0)
                        ->alwaysShowCalendar(),
                ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DispersaoIntegrado::class,
        ];
    }
}
