<?php

namespace App\Filament\Resources\Integrados\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntegradoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('nome')
                    ->label('Nome')
                    ->columnSpan(2)
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('km_rota')
                    ->label('KM Rota')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->required()
                    ->numeric(),
                TextInput::make('municipio')
                    ->label('Município')
                    ->columnSpan(1)
                    ->autocomplete(false),
                TextInput::make('estado')
                    ->label('Estado')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->default('SC'),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->default('0.00000000'),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->default('0.00000000'),
            ]);
    }
}
