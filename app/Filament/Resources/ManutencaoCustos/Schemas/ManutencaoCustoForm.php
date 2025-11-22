<?php

namespace App\Filament\Resources\ManutencaoCustos\Schemas;

use App\Enum\StatusDiversosEnum;
use App\Services\Veiculo\VeiculoCacheService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                    ->searchable(),
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
                Select::make('resultado_periodo_id')
                    ->label('Resultado Período ID')
                    ->required()
                    ->relationship('resultadoPeriodo.veiculo', 'placa', modifyQueryUsing: function ($query) {
                        $query->where('status', StatusDiversosEnum::PENDENTE->value);
                    })
            ]);
    }
}
