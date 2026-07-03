<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DesenhoPneuInput
{
    public static function make(): Select
    {
        return Select::make('desenho_pneu_id')
            ->label('Desenho Borracha')
            ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('estado_pneu', 'NOVO')->where('ativo', true))
            ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema))
            ->searchable()
            ->preload()
            ->required();
    }
}
