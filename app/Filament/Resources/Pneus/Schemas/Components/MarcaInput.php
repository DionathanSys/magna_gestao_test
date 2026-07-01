<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Filament\Resources\PneuMarcas\PneuMarcaResource;
use App\Models\PneuMarca;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MarcaInput
{
    public static function make(): Select
    {
        return Select::make('pneu_marca_id')
            ->label('Marca')
            ->relationship('marcaCatalogo', 'nome', fn ($query) => $query->where('ativo', true)->orderBy('nome'))
            ->createOptionForm(fn (Schema $schema) => PneuMarcaResource::form($schema))
            ->searchable()
            ->preload()
            ->required()
            ->live()
            ->afterStateUpdated(fn (Set $set) => $set('pneu_modelo_id', null))
            ->options(PneuMarca::query()->where('ativo', true)->orderBy('nome')->pluck('nome', 'id')->toArray());
    }
}
