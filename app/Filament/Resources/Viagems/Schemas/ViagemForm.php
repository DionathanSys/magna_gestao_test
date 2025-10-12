<?php

namespace App\Filament\Resources\Viagems\Schemas;

use Filament\Forms\Components\{
    DatePicker,
    DateTimePicker,
    Repeater,
    Select,
    TextInput,
    Toggle
};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;

class ViagemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados Viagem')
                    ->columns(12)
                    ->columnSpan(12)
                    ->schema([
                        TextInput::make('numero_viagem')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('documento_transporte')
                            ->columnSpan(2),
                        DatePicker::make('data_competencia')
                            ->columnStart(1)
                            ->columnSpan(2)
                            ->required(),
                        DateTimePicker::make('data_inicio')
                            ->columnSpan(2)
                            ->required(),
                        DateTimePicker::make('data_fim')
                            ->columnSpan(2)
                            ->required(),
                    ]),
                Section::make('Quilometragens')
                    ->columns(12)
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->schema([
                        TextInput::make('km_rodado')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_pago')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_cobrar')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_cadastro')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        Select::make('motivo_divergencia')
                            ->label('Motivo Divergência')
                            ->columnSpan(5)
                            ->native(false)
                            ->options(Enum\MotivoDivergenciaViagem::toSelectArray())
                            ->default(Enum\MotivoDivergenciaViagem::DESLOCAMENTO_OUTROS->value),
                    ]),
                Section::make('Documentos')
                ->columns(12)
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->schema([
                        RepeatableEntry::make('documentos')

                            ->table([
                                TableColumn::make('Destino'),
                                TableColumn::make('Nº Doc.'),
                                TableColumn::make('Tipo Doc.'),
                                TableColumn::make('Valor Total'),
                                TableColumn::make('Valor ICMS'),


                            ])
                            ->schema([
                                TextEntry::make('parceiro_destino'),
                                TextEntry::make('numero_documento'),
                                TextEntry::make('tipo_documento'),
                                TextEntry::make('valor_total'),
                                TextEntry::make('valor_icms'),

                            ])
                    ])

            ]);
    }
}
