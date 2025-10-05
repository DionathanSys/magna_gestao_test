<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\Services\Veiculo\VeiculoService;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

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
            ->afterStateUpdated(function (Set $set, Field $component, $state) {
                if ($state) {
                    $set('quilometragem', VeiculoService::getQuilometragemAtualByVeiculoId($state));
                    if ((new VeiculoService())->hasAgendamentoAberto($state)) {
                        $component->afterLabel([
                            Icon::make(Heroicon::ExclamationTriangle),
                            'Veículo possui agendamento aberto']);
                    } else {
                        $component->afterLabel('');
                    }
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
