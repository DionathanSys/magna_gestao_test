<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus\Schemas;

use App\Enum\Pneu\MotivoMovimentoPneuEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HistoricoMovimentoPneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pneu_id')
                    ->label('Nº de Fogo')
                    ->relationship('pneu', 'numero_fogo')
                    ->searchable()
                    ->required(),
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->required(),
                DatePicker::make('data_inicial')
                    ->label('Data Inicial')
                    ->required(),
                DatePicker::make('data_final')
                    ->label('Data Final')
                    ->required(),
                TextInput::make('km_inicial')
                    ->required()
                    ->numeric(),
                TextInput::make('km_final')
                    ->numeric(),
                TextInput::make('eixo')
                    ->required()
                    ->maxLength(255),
                TextInput::make('posicao')
                    ->label('Posição')
                    ->required()
                    ->minLength(2)
                    ->maxLength(4),
                TextInput::make('sulco_movimento')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Select::make('motivo')
                    ->required()
                    ->options(MotivoMovimentoPneuEnum::toSelectArray()),
                TextInput::make('ciclo_vida')
                    ->label('Vida')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(3),
                RichEditor::make('observacao')
                    ->label('Observação')
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }
}
