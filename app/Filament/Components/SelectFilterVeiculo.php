<?php

namespace App\Filament\Resources\Components;

use App\Services\Veiculo\VeiculoCacheService;
use Filament\Tables\Filters\SelectFilter;

class SelectFilterVeiculo
{
    public static function make(string $name = 'veiculo_id'): SelectFilter
    {
        return SelectFilter::make($name)
            ->label('Veículo')
            ->options(VeiculoCacheService::getPlacasAtivasForSelect())
            ->searchable();
    }
}