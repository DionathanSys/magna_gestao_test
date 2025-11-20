<?php

namespace App\Filament\Resources\ResultadoPeriodos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ResultadoPeriodoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $veiculo = \App\Models\Veiculo::find($get('veiculo_id'));
                        if ($veiculo) {
                            $set('tipo_veiculo_id', $veiculo->tipo_veiculo_id);
                            return;
                        }
                        $set('tipo_veiculo_id', null);
                    }),
                Select::make('tipo_veiculo_id')
                    ->relationship('tipoVeiculo', 'descricao')
                    ->searchable()
                    ->required(),
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->required(),
                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->required(),
                TextInput::make('km_inicial')
                    ->label('Km Inicial')
                    ->numeric(),
                TextInput::make('km_final')
                    ->label('Km Final')
                    ->visibleOn('edit')
                    ->numeric(),
                TextInput::make('km_percorrido')
                    ->label('Km Percorrido')
                    ->visibleOn('edit')
                    ->numeric(),
            ]);
    }
}
