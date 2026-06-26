<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models\PneuMarca;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;

class MarcaInput
{
    public static function make(): Select
    {
        return Select::make('pneu_marca_id')
            ->label('Marca')
            ->searchable()
            ->preload()
            ->required()
            ->live()
            ->afterStateUpdated(fn (Set $set) => $set('pneu_modelo_id', null))
            ->options(PneuMarca::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id')->toArray());
    }
}
