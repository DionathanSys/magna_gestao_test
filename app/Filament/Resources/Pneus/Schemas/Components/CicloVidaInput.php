<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use Filament\Forms\Components\TextInput;

class CicloVidaInput
{
    public static function make(): TextInput
    {
        return TextInput::make('ciclo_vida')
            ->label('Vida')
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->maxValue(9);
    }
}
