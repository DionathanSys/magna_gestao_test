<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models\PneuModelo;
use Filament\Forms\Components\Select;

class ModeloInput
{
    public static function make(): Select
    {
        return Select::make('pneu_modelo_id')
            ->label('Modelo')
            ->searchable()
            ->preload()
            ->options(PneuModelo::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id')->toArray());
    }
}
