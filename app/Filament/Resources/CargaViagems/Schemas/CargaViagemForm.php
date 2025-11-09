<?php

namespace App\Filament\Resources\CargaViagems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CargaViagemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Select::make('viagem_id')
                    ->columnSpan(1)
                    ->relationship('viagem', 'numero_viagem')
                    ->searchable()
                    ->required(),
                Select::make('integrado_id')
                    ->columnSpan(1)
                    ->relationship('integrado', 'nome')
                    ->searchable()
                    ->required(),
            ]);
    }
}
