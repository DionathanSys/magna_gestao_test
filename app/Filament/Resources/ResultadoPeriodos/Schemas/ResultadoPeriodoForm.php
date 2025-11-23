<?php

namespace App\Filament\Resources\ResultadoPeriodos\Schemas;

use App\Enum\StatusDiversosEnum;
use App\Services\Veiculo\VeiculoCacheService;
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
            ->columns(6)
            ->components([
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->options(VeiculoCacheService::getPlacasAtivasForSelect())
                    ->columnSpan(3)
                    ->searchable()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $veiculo = \App\Models\Veiculo::find($get('veiculo_id'));
                        if ($veiculo) {
                            $set('tipo_veiculo_id', $veiculo->tipo_veiculo_id);
                            return;
                        }
                        $set('tipo_veiculo_id', null);
                    }),
                Select::make('status')
                    ->options(StatusDiversosEnum::toSelectArray())
                    ->default(StatusDiversosEnum::PENDENTE->value)
                    ->columnSpan(3)
                    ->required(),
                Select::make('tipo_veiculo_id')
                    ->relationship('tipoVeiculo', 'descricao')
                    ->columnSpanFull()
                    ->searchable()
                    ->required(),
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->columnSpan(3)
                    ->required(),
                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->columnSpan(3)
                    ->required(),
            ]);
    }
}
