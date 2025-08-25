<?php

namespace App\Filament\Resources\DocumentoFretes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentoFreteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'id')
                    ->required(),
                Select::make('integrado_id')
                    ->relationship('integrado', 'id'),
                TextInput::make('numero_documento'),
                TextInput::make('documento_transporte'),
                TextInput::make('tipo_documento'),
                DatePicker::make('data_emissao')
                    ->required(),
                TextInput::make('valor_total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('valor_icms')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('municipio'),
                TextInput::make('estado'),
            ]);
    }
}
