<?php

namespace App\Filament\Resources\OrdemServicos\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use App\Enum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Utilities\Set;

class OrdemServicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['md' => 2, 'xl' => 4])
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Informações')
                            ->columns([
                                'sm' => 1,
                                'md' => 4,
                                'lg' => 10,
                            ])
                            ->schema([
                                static::getVeiculoIdFormField()
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                static::getQuilometragemFormField()
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                static::getTipoManutencaoFormField()
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                static::getDataInicioFormField()
                                    ->columnStart(1)
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                static::getDataFimFormField()
                                    ->visibleOn('edit')
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                static::getStatusFormField()
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                static::getStatusSankhyaFormField()
                                    ->columnSpan([
                                        'sm' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),

                                Section::make('Manutenção Externa')
                                    ->columnSpanFull()
                                    ->columns(8)
                                    ->schema([
                                        static::getParceiroIdFormField()
                                            ->columnSpan(4),
                                    ])
                                    ->collapsed()
                                    ->collapsible(),
                            ]),
                        Tabs\Tab::make('Ordens Sankhya')
                            ->columns(3)
                            ->visibleOn('edit')
                            ->schema([
                                Repeater::make('sankhyaId')
                                    // ->relationship()
                                    ->table([
                                        TableColumn::make('Nro. OS')->width('100px'),
                                        TableColumn::make('Nro. OS Sankhya')->width('100px'),
                                        TableColumn::make('Data de Criação')->width('150px'),
                                    ])
                                    ->columns(10)
                                    ->schema([
                                        TextInput::make('ordem_servico_id')
                                            ->label('Nro. OS')
                                            ->columnSpan(1)
                                            ->required(),
                                        TextInput::make('ordem_sankhya_id')
                                            ->label('Nro. OS Sankhya')
                                            ->columnSpan(1)
                                            ->required(),
                                        DatePicker::make('created_at')
                                            ->label('Data de Criação')
                                            ->columnSpan(1)
                                            ->date('d/m/Y')
                                            ->readOnly(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getVeiculoIdFormField(): Select
    {
        return Select::make('veiculo_id')
            ->label('Veículo')
            ->searchPrompt('Buscar Placa')
            ->placeholder('Buscar ...')
            ->native(false)
            ->columnSpan(2)
            ->required()
            ->relationship('veiculo', 'placa')
            ->searchable()
            ->preload()
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state) {
                    $veiculo = \App\Models\Veiculo::with('kmAtual')->find($state);
                    if ($veiculo) {
                        $set('quilometragem', $veiculo->kmAtual?->quilometragem ?? 0);
                    }
                } else {
                    $set('quilometragem', null);
                }
            });
    }

    public static function getQuilometragemFormField(): TextInput
    {
        return TextInput::make('quilometragem')
            ->label('Quilometragem')
            ->columnSpan(2)
            ->numeric()
            ->minValue(0)
            ->maxValue(999999)
            ->required();
    }

    public static function getTipoManutencaoFormField(): Select
    {
        return Select::make('tipo_manutencao')
            ->label('Tipo de Manutenção')
            ->columnSpan(2)
            ->options(Enum\OrdemServico\TipoManutencaoEnum::toSelectArray())
            ->required()
            ->default(Enum\OrdemServico\TipoManutencaoEnum::CORRETIVA->value);
    }

    public static function getDataInicioFormField(): DateTimePicker
    {
        return DateTimePicker::make('data_inicio')
            ->label('Dt. Inicio')
            ->columnSpan(2)
            ->seconds(false)
            ->required()
            ->maxDate(now())
            ->default(now());
    }

    public static function getDataFimFormField(): DateTimePicker
    {
        return DateTimePicker::make('data_fim')
            ->label('Dt. Fim')
            ->columnSpan(2)
            ->seconds(false)
            ->maxDate(now());
    }

    public static function getStatusFormField(): Select
    {
        return Select::make('status')
            ->label('Status')
            ->columnSpan(2)
            ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
            ->default(Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value)
            ->required();
    }

    public static function getStatusSankhyaFormField(): Select
    {
        return Select::make('status_sankhya')
            ->label('Sankhya')
            ->columnSpan(2)
            ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
            ->default(Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value)
            ->required();
    }

    public static function getParceiroIdFormField(): Select
    {
        return Select::make('parceiro_id')
            ->label('Parceiro')
            ->columnSpan(2)
            ->relationship('parceiro', 'nome')
            ->searchable()
            ->preload()
            ->searchPrompt('Buscar Parceiro')
            ->placeholder('Buscar ...');
    }
}
