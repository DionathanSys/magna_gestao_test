<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\Services\Veiculo\VeiculoService;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;

class OrdemServicoVeiculoInput
{
    public static function make($column = 'veiculo_id'): Select
    {
        return Select::make($column)
            ->label('Veículo')
            ->searchPrompt('Buscar Veículo')
            ->placeholder('Buscar ...')
            ->autofocus(true)
            ->relationship('veiculo', 'placa')
            ->required()
            ->searchable()
            ->preload()
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state) {
                    $set('quilometragem', VeiculoService::getQuilometragemAtualByVeiculoId($state));
                } else {
                    $set('quilometragem', null);
                }
            })
            ->columnSpan([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ]);
    }
}
