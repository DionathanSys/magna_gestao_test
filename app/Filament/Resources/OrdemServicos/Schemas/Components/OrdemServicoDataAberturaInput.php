<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use Filament\Forms\Components\DateTimePicker;

class OrdemServicoDataAberturaInput
{
    public static function make($column = 'data_inicio'): DateTimePicker
    {
        return DateTimePicker::make($column)
            ->label('Dt. Inicio')
            ->columnSpan(2)
            ->seconds(false)
            ->required()
            ->maxDate(now())
            ->default(now());
    }
}
