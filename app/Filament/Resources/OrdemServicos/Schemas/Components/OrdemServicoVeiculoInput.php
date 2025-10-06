<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\Services\Veiculo\VeiculoService;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;

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
                    Log::debug('Veículo selecionado: ' . $state);
                    $set('quilometragem', VeiculoService::getQuilometragemAtualByVeiculoId($state));
                    $possuiAgendamento = (new VeiculoService())->hasAgendamentoAberto($state);
                    Log::debug('Possui agendamento aberto: ' . ($possuiAgendamento ? 'Sim' : 'Não'));
                    if ($possuiAgendamento) {
                        $component->belowContent([
                            Icon::make(Heroicon::InformationCircle)->color(Color::Indigo),
                            Text::make('Possui agendamento em aberto.')->weight(FontWeight::Bold)->color(Color::Amber),
                        ]);
                    }
                } else {
                    Log::debug('Nenhum veículo selecionado.');
                    $set('quilometragem', null);
                    // $component->afterLabel(null);
                }
            })
            ->columnSpan([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ]);
    }
}
