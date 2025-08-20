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
            ->components([
                Select::make('viagem_id')
                    ->relationship('viagem', 'numero_viagem')
                    ->searchable()
                    ->required(),
                Select::make('integrado_id')
                    ->relationship('integrado', 'nome')
                    ->required(),
                TextInput::make('documento_frete_id')
                    ->numeric(),
            ]);
    }
}
