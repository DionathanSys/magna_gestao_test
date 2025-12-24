<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\{ToggleButtons, Select, TagsInput, FileUpload, Repeater};
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Enum\ClienteEnum;
use Filament\Schemas\Components\Section;

class ViagemBugioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Detalhes da Viagem')
                            ->columns(['md' => 4, 'xl' => 6])
                            ->columnSpan(1)
                            ->components([
                                Select::make('motorista')
                                    ->label('Motorista')
                                    ->columnSpan(['md' => 3, 'xl' => 4])
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                        if ($state) {
                                            $placa = collect(db_config('config-bugio.motoristas'))
                                                ->firstWhere('cpf', $state)['placa'] ?? null;
                                            $set('veiculo', $placa);
                                        } else {
                                            $set('veiculo', null);
                                        }
                                    }),
                                Select::make('veiculo')
                                    ->label('Veículo')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => collect(db_config('config-bugio.veiculos'))->pluck('placa', 'placa')->toArray())
                                    ->required()
                                    ->reactive(),
                                TextInput::make('numero_sequencial')
                                    ->label('Nº Sequencial')
                                    ->columnStart(1)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->nullable(),
                                TagsInput::make('nro_notas')
                                    ->label('Nº de Notas Fiscais')
                                    ->required()
                                    ->separator(',')
                                    ->splitKeys(['Tab', ' '])
                                    ->trim()
                                    ->columnSpan(['md' => 1, 'xl' => 2]),
                                ToggleButtons::make('tipo_documento')
                                    ->label('Tipo de Documento')
                                    ->inline()
                                    ->options([
                                        'cte' => 'CT-e',
                                        'nfse' => 'NFS-e',
                                    ])
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                        if ($state === 'nfse') {
                                            $set('cte_retroativo', false);
                                            $set('cte_complementar', false);
                                            $set('cte_referencia', null);
                                        }
                                    })
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->default('cte')
                                    ->required()
                                    ->reactive(),
                                Toggle::make('cte_retroativo')
                                    ->label('CTe Retroativo')
                                    ->columnStart(1)
                                    ->columnSpan(2)
                                    ->inline(false)
                                    ->default(true),
                                Toggle::make('cte_complementar')
                                    ->label('CTe Complementar')
                                    ->columnSpan(2)
                                    ->inline(false)
                                    ->default(false),
                                TextInput::make('cte_referencia')
                                    ->label('CTe de Referência')
                                    ->requiredIf('cte_complementar', true)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->nullable(),
                                TextInput::make('km_total')
                                    ->label('KM Total')
                                    ->columnStart(1)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->numeric()
                                    ->required()
                                    ->readOnly()
                                    ->default(0)
                                    ->minValue(0)
                                    ->reactive(),
                                TextInput::make('valor_frete')
                                    ->label('Valor do Frete')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->disabled()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->reactive(),
                                Repeater::make('data-integrados')
                                    ->label('Integrados')
                                    ->columns(['md' => 4, 'xl' => 6])
                                    ->columnSpan(['md' => 2, 'xl' => 5])
                                    ->defaultItems(1)
                                    ->addActionLabel('Adicionar Integrado')
                                    ->deletable(true)
                                    ->addable(false)
                                    ->minItems(1)
                                    ->maxItems(1)
                                    ->schema([
                                        Select::make('integrado_id')
                                            ->label('Integrado')
                                            ->searchable()
                                            ->columnSpan(['md' => 2, 'xl' => 4])
                                            ->preload()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->options(\App\Models\Integrado::query()
                                                ->where('cliente', ClienteEnum::BUGIO)
                                                ->pluck('nome', 'id'))
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                                if ($state) {
                                                    $integrado = \App\Models\Integrado::find($state);
                                                    $kmRota = $integrado?->km_rota;
                                                    $municipio = $integrado?->municipio;
                                                    $kmTotal = $get('../../km_total') + ($kmRota ?? 0);
                                                    $frete = self::calcularFrete($kmTotal);
                                                    $set('km_rota', $kmRota ?? 0);
                                                    $set('municipio', $municipio ?? '');
                                                    $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                                    $set('../../valor_frete', number_format($frete, 2, '.', ''));
                                                } else {
                                                    $kmTotal = $get('../../km_total') - $get('km_rota', 0);
                                                    $frete = self::calcularFrete($kmTotal);
                                                    $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                                    $set('../../valor_frete', number_format($frete, 2, '.', ''));
                                                    $set('km_rota', 0);
                                                    $set('municipio', null);
                                                }
                                            }),
                                        TextInput::make('km_rota')
                                            ->label('KM Rota')
                                            ->columnSpan(['md' => 1, 'xl' => 2])
                                            ->numeric()
                                            ->minValue(0)
                                            ->required()
                                            ->default(0)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                                if ($state !== $old) {
                                                    $kmTotal = $get('../../km_total') - ($old ?? 0) + ($state ?? 0);
                                                    $frete = self::calcularFrete($kmTotal);
                                                    $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                                    $set('../../valor_frete', number_format($frete, 2, '.', ''));
                                                }
                                            })
                                            ->live(onBlur: true),
                                        TextInput::make('municipio')
                                            ->label('Municipio')
                                            ->columnSpanFull()
                                            ->readOnly(),
                                    ]),
                            ]),
                        Section::make('Documentos Fiscais')
                            ->columnSpan(1)
                            ->components([
                                FileUpload::make('anexos')
                                    ->columnSpan(['md' => 4, 'xl' => 6])
                                    ->label('Anexos')
                                    ->columnStart(1)
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->preserveFilenames()
                                    ->directory('cte')
                                    ->visibility('private')
                                    ->required(),

                            ])
                    ])
            ]);
    }

    public static function calcularFrete(float $kmTotal): float
    {
        $valorQuilometro = db_config('config-bugio.valor-quilometro', 0);

        return $valorQuilometro * $kmTotal;
    }
}
