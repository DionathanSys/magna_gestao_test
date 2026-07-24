<?php

namespace App\Filament\Components;

use App\Services\Veiculo\VeiculoCacheService;
use Filament\Forms\Components\Select;

class SelectFilterVeiculo
{
    public static function make(string $name = 'veiculo_id'): Select
    {
        return Select::make($name)
            ->label('Veículo')
            ->options(VeiculoCacheService::getPlacasAtivasForSelect())
            ->searchable();
    }
}
