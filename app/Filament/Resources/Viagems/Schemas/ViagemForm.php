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
            ->components([
                TextInput::make('numero_viagem')
                    ->required(),
                TextInput::make('numero_custo_frete'),
                TextInput::make('documento_transporte'),
                Section::make('Quilometragens')
                    ->columns(4)
                    ->schema([
                        TextInput::make('km_rodado')
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_pago')
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_cobrar')
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_rota_corrigido')
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
                    ->columns(4)
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
