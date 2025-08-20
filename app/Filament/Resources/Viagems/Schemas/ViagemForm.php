<?php

namespace App\Filament\Resources\Viagems\Schemas;

use Filament\Forms\Components\{
    DatePicker,
    DateTimePicker,
    Select,
    TextInput,
    Toggle
};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enum;

class ViagemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                TextInput::make('numero_viagem')
                    ->required()
                    ->columnSpan(1),
                TextInput::make('numero_custo_frete')
                    ->columnSpan(1),
                TextInput::make('documento_transporte')
                    ->columnSpan(1),
                Section::make('Quilometragens')
                    ->columnStart(1)
                    ->schema([
                        TextInput::make('km_rodado')
                            ->columnSpan(1)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_pago')
                            ->columnSpan(1)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_cobrar')
                            ->columnSpan(1)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_rota_corrigido')
                            ->columnSpan(1)
                            ->required()
                            ->numeric()
                            ->default(0),
                        Select::make('motivo_divergencia')
                            ->label('Motivo Divergência')
                            ->native(false)
                            ->options(Enum\MotivoDivergenciaViagem::toSelectArray())
                            ->default(Enum\MotivoDivergenciaViagem::DESLOCAMENTO_OUTROS->value),
                    ]),
                Section::make('Datas')
                    ->schema([
                        DatePicker::make('data_competencia')
                            ->required(),
                        DateTimePicker::make('data_inicio')
                            ->required(),
                        DateTimePicker::make('data_fim')
                            ->required(),
                    ]),
                Toggle::make('conferido')
                    ->required(),
            ]);
    }
}
