<?php

namespace App\Filament\Resources\Agendamentos\Schemas;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AgendamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'id')
                    ->required(),
                Select::make('ordem_servico_id')
                    ->relationship('ordemServico', 'id'),
                DatePicker::make('data_agendamento'),
                DatePicker::make('data_limite'),
                DatePicker::make('data_realizado'),
                Select::make('servico_id')
                    ->relationship('servico', 'id')
                    ->required(),
                Select::make('plano_preventivo_id')
                    ->relationship('planoPreventivo', 'id'),
                TextInput::make('posicao'),
                Select::make('status')
                    ->options(StatusOrdemServicoEnum::class)
                    ->required(),
                TextInput::make('observacao'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                Select::make('parceiro_id')
                    ->relationship('parceiro', 'id'),
            ]);
    }
}
