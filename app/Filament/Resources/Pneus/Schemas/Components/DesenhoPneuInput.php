<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class DesenhoPneuInput
{
    public static function make(): Select
    {
        return Select::make('desenho_pneu_id')
            ->label('Desenho Borracha')
            ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('estado_pneu', 'NOVO'))
            ->searchable()
            ->preload()
            ->required()
            // ->createOptionForm(fn(Schema $schema) => DesenhoPneuResource::form($schema))
            ;
    }
}
