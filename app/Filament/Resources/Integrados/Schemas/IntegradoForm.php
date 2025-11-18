<?php

namespace App\Filament\Resources\Integrados\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Enum;
use Filament\Forms\Components\Toggle;

class IntegradoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('nome')
                    ->label('Nome')
                    ->columnSpan(4)
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('municipio')
                    ->label('Município')
                    ->columnSpan(2)
                    ->autocomplete(false),
                TextInput::make('estado')
                    ->label('Estado')
                    ->columnSpan(1)
                    ->autocomplete(false)
                    ->default('SC'),
                TextInput::make('km_rota')
                    ->label('KM Rota')
                    ->columnSpan(1)
                    ->columnStart(1)
                    ->autocomplete(false)
                    ->required()
                    ->numeric(),
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
                Toggle::make('alerta_viagem')
                    ->label('Alerta Viagem')
                    ->columnStart(1)
                    ->columnSpan(2)
                    ->default(false),
                Select::make('cliente')
                    ->label('Cliente')
                    ->required()
                    ->native(false)
                    ->columnStart(1 )
                    ->columnSpan(2)
                    ->options(Enum\ClienteEnum::toSelectArray())
            ]);
    }
}
