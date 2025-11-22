<?php

namespace App\Filament\Resources\ManutencaoCustos\Schemas;

use App\Models;
use App\Enum\StatusDiversosEnum;
use App\Services\Veiculo\VeiculoCacheService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ManutencaoCustoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->required()
                    ->options(VeiculoCacheService::getPlacasAtivasForSelect())
                    ->searchable()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, $state) {
                        if($state){
                            $resultadoPeriodo = Models\ResultadoPeriodo::where('veiculo_id', $state)
                                ->where('status', StatusDiversosEnum::PENDENTE->value)
                                ->first();
                            $set('resultado_periodo_id', $resultadoPeriodo?->id);
                            return;
                        }
                        $set('resultado_periodo_id', null);
                    }),
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->required(),
                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->required(),
                TextInput::make('custo_total')
                    ->label('Custo Total R$')
                    ->prefix('R$')
                    ->required()
                    ->numeric(),
                TextInput::make('resultado_periodo_id')
                    ->label('Resultado Período ID')
                    ->required()
            ]);
    }
}
