<?php

namespace App\Filament\Resources\ResultadoPeriodos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ResultadoPeriodoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'placa')
                    ->required(),
                Select::make('tipo_veiculo_id')
                    ->relationship('tipoVeiculo', 'descricao')
                    ->required(),
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->required(),
                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->required(),
                TextInput::make('km_inicial')
                    ->label('Km Inicial')
                    ->required()
                    ->numeric(),
                TextInput::make('km_final')
                    ->label('Km Final')
                    ->required()
                    ->numeric(),
                TextInput::make('km_percorrido')
                    ->label('Km Percorrido')
                    ->numeric(),
            ]);
    }
}
