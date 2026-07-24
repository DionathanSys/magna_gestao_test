<?php

namespace App\Filament\Pages;

use App\Models\Veiculo;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected function getHeaderActions(): array
    {

        return [
            FilterAction::make()
                ->schema([
                    Select::make('placa')
                        ->label('Placa')
                        ->options(fn () => Veiculo::all()->pluck('placa', 'id'))
                        ->searchable()
                        ->placeholder('Selecione uma placa'),
                    DatePicker::make('dataInicial')
                        ->maxDate(fn (UtilitiesGet $get) => $get('dataFinal') ?: now())
                        ->reactive(),
                    DatePicker::make('dataFinal')
                        ->maxDate(now())
                        ->reactive(),
                    Checkbox::make('conferido')
                        ->label('Apenas Viagens Conferidas'),
                ]),
        ];
    }
}
