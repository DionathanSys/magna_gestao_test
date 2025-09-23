<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus\Schemas;

use App\Enum\Pneu\MotivoMovimentoPneuEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class HistoricoMovimentoPneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->components([
                Select::make('pneu_id')
                    ->label('Nº de Fogo')
                    ->columnSpan(2)
                    ->relationship('pneu', 'numero_fogo')
                    ->searchable()
                    ->disabled(fn(): bool => ! Auth::user()->is_admin)
                    ->required(),
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->columnSpan(2)
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->required(),
                DatePicker::make('data_inicial')
                    ->label('Data Inicial')
                    ->columnSpan(2)
                    ->required(),
                DatePicker::make('data_final')
                    ->label('Data Final')
                    ->columnSpan(2)
                    ->required(),
                TextInput::make('km_inicial')
                    ->label('KM Aplicação')
                ->columnSpan(2)
                    ->required()
                    ->numeric(),
                TextInput::make('km_final')
                    ->label('KM Remoção')
                    ->columnSpan(2)
                    ->numeric(),
                TextInput::make('eixo')
                    ->columnSpan(2)
                    ->required()
                    ->maxLength(255),
                TextInput::make('posicao')
                    ->label('Posição')
                    ->columnSpan(2)
                    ->required()
                    ->minLength(2)
                    ->maxLength(4),
                TextInput::make('sulco_movimento')
                    ->columnSpan(2)
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Select::make('motivo')
                    ->columnSpan(2)
                    ->required()
                    ->options(MotivoMovimentoPneuEnum::toSelectArray()),
                TextInput::make('ciclo_vida')
                    ->label('Vida')
                    ->required()
                    ->columnSpan(2)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(3),
                RichEditor::make('observacao')
                    ->label('Observação')
                    ->columnSpanFull()
                    ->maxLength(255),
                FileUpload::make('anexos')
                    ->image()
                    ->openable()
                    ->downloadable()
                    ->multiple()
                    ->panelLayout('grid')
                    ->disk('local')
                    ->directory('pneus/movimentacoes')
                    ->visibility('private')
                    ->columnSpanFull()
            ]);
    }
}
