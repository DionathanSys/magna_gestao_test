<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\{ToggleButtons, Select, TagsInput, FileUpload, Repeater, DatePicker};
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;
use App\Services\FreteCalculador;

class ViagemBugioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(['md' => 1, 'lg' => 1, 'xl' => 1, '2xl' => 2])
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Detalhes da Viagem')
                            ->columns(['md' => 4, 'xl' => 6])
                            ->columnSpan(fn($operation) => $operation === 'create' ? 1 : 2)
                            ->components([
                                Select::make('motorista')
                                    ->label('Motorista')
                                    ->visibleOn('create')
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
                                    ->visibleOn('create')
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
                                DatePicker::make('data_competencia')
                                    ->label('Data de Competência')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->default(now()),
                                TextInput::make('info_adicionais.peso')
                                    ->label('Peso Carga')
                                    ->minValue(0)
                                    ->required(),
                                TagsInput::make('nro_notas')
                                    ->label('Nº de Notas Fiscais')
                                    ->columnStart(1)
                                    ->required()
                                    ->splitKeys(['Tab', ' '])
                                    ->trim()
                                    ->columnSpan(['md' => 1, 'xl' => 2]),
                                ToggleButtons::make('info_adicionais.tipo_documento')
                                    ->label('Tipo de Documento')
                                    ->inline()
                                    ->options(TipoDocumentoEnum::toSelectArray())
                                    ->default(TipoDocumentoEnum::CTE->value)
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                        if ($state === TipoDocumentoEnum::NFS->value) {
                                            $set('info_adicionais.cte_retroativo', false);
                                            $set('info_adicionais.cte_referencia', null);
                                        }
                                    })
                                    ->columnSpan(['md' => 3, 'xl' => 4])
                                    ->required()
                                    ->reactive(),
                                Toggle::make('info_adicionais.cte_retroativo')
                                    ->label('CTe Retroativo')
                                    ->columnStart(1)
                                    ->columnSpan(2)
                                    ->inline(false)
                                    ->grow()
                                    ->default(true),
                                TextInput::make('info_adicionais.cte_referencia')
                                    ->label('CTe de Referência')
                                    ->requiredIf('info_adicionais.tipo_documento', TipoDocumentoEnum::CTE_COMPLEMENTO->value)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->nullable(),
                                TextInput::make('km_total')
                                    ->label('KM Total')
                                    ->columnStart(1)
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->numeric()
                                    ->required()
                                    ->readOnly()
                                    ->default(0)
                                    // ->minValue(0)
                                    ->reactive(),
                                TextInput::make('valor_frete')
                                    ->label('Valor do Frete')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->visibleOn('create')
                                    ->disabled()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->reactive(),
                                Select::make('destinos.integrado_id')
                                    ->label('Integrado')
                                    ->visibleOn('create')
                                    ->columnStart(1)
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
                                            $kmTotal = $kmRota ?? 0;
                                            $frete = self::calcularFrete($kmTotal);
                                            $set('destinos.km_rota', $kmRota ?? 0);
                                            $set('destinos.municipio', $municipio ?? '');
                                            $set('km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('valor_frete', number_format($frete, 2, '.', ''));

                                            // Atualiza detalhes do piso mínimo
                                            $temRetornoVazio = $get('info_adicionais.tem_retorno_vazio') ?? false;
                                            self::atualizarDetalhes($set, $get, $kmTotal, $temRetornoVazio);

                                            if (Str::upper($integrado->municipio) == 'GUATAMBU') {
                                                $set('info_adicionais.tipo_documento', TipoDocumentoEnum::NFS->value);
                                                $set('info_adicionais.cte_retroativo', false);
                                                $set('info_adicionais.cte_referencia', null);
                                            }
                                        } else {
                                            $kmTotal = $get('km_total') - $get('km_rota', 0);
                                            $frete = self::calcularFrete($kmTotal);
                                            $set('km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('valor_frete', number_format($frete, 2, '.', ''));
                                            $set('destinos.km_rota', 0);
                                            $set('destinos.municipio', null);

                                            // Atualiza detalhes do piso mínimo
                                            $temRetornoVazio = $get('info_adicionais.tem_retorno_vazio') ?? false;
                                            self::atualizarDetalhes($set, $get, $kmTotal, $temRetornoVazio);
                                        }
                                    }),
                                TextInput::make('destinos.km_rota')
                                    ->label('KM Rota')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->default(0)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                        if ($state !== $old) {
                                            $kmTotal = $get('km_total') - ($old ?? 0) + ($state ?? 0);
                                            $frete = self::calcularFrete($kmTotal);
                                            $set('km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('valor_frete', number_format($frete, 2, '.', ''));

                                            // Atualiza detalhes do piso mínimo
                                            $temRetornoVazio = $get('info_adicionais.tem_retorno_vazio') ?? false;
                                            self::atualizarDetalhes($set, $get, $kmTotal, $temRetornoVazio);
                                        }
                                    })
                                    ->live(onBlur: true),
                                TextInput::make('destinos.municipio')
                                    ->label('Municipio')
                                    ->visibleOn('create')
                                    ->columnSpanFull()
                                    ->readOnly(),
                                Toggle::make('info_adicionais.tem_retorno_vazio')
                                    ->label('Tem Retorno Vazio')
                                    ->visibleOn('create')
                                    ->columnStart(1)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->inline(false)
                                    ->default(false)
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $kmTotal = $get('km_total') ?? 0;
                                        $temRetornoVazio = $get('info_adicionais.tem_retorno_vazio') ?? false;
                                        self::atualizarDetalhes($set, $get, $kmTotal, $temRetornoVazio);
                                    }),
                                TextInput::make('viagem_id')
                                    ->label('Viagem ID')
                                    ->visibleOn('edit'),
                                TextInput::make('documento_frete_id')
                                    ->label('Documento Frete ID')
                                    ->visibleOn('edit'),
                            ]),
                        Section::make('Documentos Fiscais')
                            ->columnSpan(1)
                            ->hiddenOn('edit')
                            ->components([
                                FileUpload::make('anexos')
                                    ->columnSpan(['md' => 4, 'xl' => 6])
                                    ->label('Anexos')
                                    ->columnStart(1)
                                    ->preserveFilenames()
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->directory('cte')
                                    ->visibility('private')
                                    ->required(),

                            ]),
                        Section::make('Detalhes do Piso Mínimo')
                            ->columnSpanFull()
                            ->visibleOn('create')
                            ->columns(['md' => 4, 'xl' => 6])
                            ->columnStart(1)
                            ->collapsed()
                            ->collapsible()
                            ->components([
                                TextInput::make('detalhes_piso.km_ida')
                                    ->label('KM Ida (km_total ÷ 2)')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->suffix(' km')
                                    ->readOnly()
                                    ->disabled(),
                                TextInput::make('detalhes_piso.ccd')
                                    ->label('CCD (Coeficiente)')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->readOnly()
                                    ->disabled(),
                                TextInput::make('detalhes_piso.cc')
                                    ->label('CC (Carga/Descarga)')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->disabled(),
                                TextInput::make('detalhes_piso.valor_ida')
                                    ->label('Valor Ida: (KM Ida × CCD) + CC')
                                    ->visibleOn('create')
                                    ->columnStart(1)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->disabled(),
                                TextInput::make('detalhes_piso.valor_retorno')
                                    ->label('Valor Retorno: 0.92 × KM Ida × CCD')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->disabled()
                                    ->visible(fn(Get $get) => (bool) $get('info_adicionais.tem_retorno_vazio')),
                                TextInput::make('detalhes_piso.piso_minimo')
                                    ->label('Piso Mínimo (Ida + Retorno)')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->disabled(),
                                TextInput::make('detalhes_piso.frete_antigo')
                                    ->label('Frete Antigo: R$/km × km_total')
                                    ->visibleOn('create')
                                    ->columnStart(1)
                                    ->columnSpan(['md' => 1, 'xl' => 2])
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->disabled(),
                                TextInput::make('detalhes_piso.frete_final')
                                    ->label('Frete Final (MAX dos dois)')
                                    ->visibleOn('create')
                                    ->columnSpan(['md' => 2, 'xl' => 2])
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                    ])
            ]);
    }

    public static function calcularFrete(float $kmTotal): float
    {
        $valorQuilometro = db_config('config-bugio.valor-quilometro', 0);

        return $valorQuilometro * $kmTotal;
    }

    /**
     * Atualiza os campos de detalhes do piso mínimo.
     */
    private static function atualizarDetalhes(Set $set, Get $get, float $kmTotal, bool $temRetornoVazio): void
    {
        $resultado = FreteCalculador::calcularPisoMinimo($kmTotal, $temRetornoVazio);

        $set('detalhes_piso.km_ida', number_format($resultado['km_ida'], 2, '.', ''));
        $set('detalhes_piso.ccd', number_format($resultado['ccd'], 4, '.', ''));
        $set('detalhes_piso.cc', number_format($resultado['cc'], 2, '.', ''));
        $set('detalhes_piso.valor_ida', number_format($resultado['valor_ida'], 2, '.', ''));
        $set('detalhes_piso.valor_retorno', number_format($resultado['valor_retorno'], 2, '.', ''));
        $set('detalhes_piso.piso_minimo', number_format($resultado['piso_minimo'], 2, '.', ''));
        $set('detalhes_piso.frete_antigo', number_format($resultado['frete_antigo'], 2, '.', ''));
        $set('detalhes_piso.frete_final', number_format($resultado['frete_final'], 2, '.', ''));
    }
}
