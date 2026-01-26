<?php

namespace App\Filament\Resources\Viagems\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use Filament\Actions\{ActionGroup, BulkAction, BulkActionGroup, CreateAction, DeleteBulkAction, EditAction, ImportAction, ReplicateAction,};
use Filament\Tables\Columns\{ColumnGroup, IconColumn, SelectColumn, StaticAction, TextColumn, TextInputColumn,};
use Filament\Tables\Table;
use App\{Models, Services, Enum};
use App\Filament\Components\RegistrosSemVinculoResultadoFilter;
use App\Filament\Resources\{DocumentoFretes, Viagems};
use App\Filament\Actions\DissociateResultadoPeriodoBulkAction;
use App\Filament\Resources\Viagems\ViagemResource;
use App\Models\Viagem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\{Filter, Indicator, QueryBuilder, SelectFilter, TernaryFilter};
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Enums\RecordActionsPosition;
use Illuminate\Support\Collection;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ViagemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->select('viagens.*')
                    ->selectRaw("
                        COALESCE(
                            (SELECT GROUP_CONCAT(DISTINCT CONCAT(integrados.nome, ' - ', integrados.municipio) SEPARATOR '<br>')
                             FROM cargas_viagem
                             JOIN integrados ON integrados.id = cargas_viagem.integrado_id
                             WHERE cargas_viagem.viagem_id = viagens.id
                            ), ''
                        ) as integrados_nomes_view
                    ")
                    ->selectRaw("
                        COALESCE(
                            (
                                SELECT GROUP_CONCAT(
                                    CONCAT(
                                        'Nº ',
                                        documentos_frete.numero_documento,
                                        ' - R$ ',
                                        REPLACE(
                                            ROUND(documentos_frete.valor_liquido / 100, 2),
                                            '.', ','
                                        )
                                    )
                                    SEPARATOR '<br>'
                                )
                                FROM documentos_frete
                                WHERE documentos_frete.viagem_id = viagens.id
                            ), ''
                        ) as documentos_frete_resumo_view
                    ")
                    ->selectRaw("
                        COALESCE(
                            (SELECT GROUP_CONCAT(documentos_frete.parceiro_destino SEPARATOR ';<br>')
                             FROM documentos_frete
                             WHERE documentos_frete.viagem_id = viagens.id
                            ), ''
                        ) as parceiro_frete_view
                    ")
                    ->with([
                        // 'cargas' load removed to save performance (calculated via subquery)
                        // 'documentos' load removed to save performance
                        'veiculo:id,placa',
                        'comentarios.creator:id,name',
                        'checker:id,name',
                        'creator:id,name',
                        'updater:id,name',
                        'resultadoPeriodo:id,data_inicio',
                    ])
                    // antecipa o cálculo de existência para evitar N+1
                    ->withExists(['comentarios'])
                    ->withCount(['cargas', 'documentos']);
            })
            ->poll(null)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->disabledClick()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('numero_viagem')
                    ->label('Nº Viagem')
                    ->width('1%')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->disabledClick(),
                TextColumn::make('qtde_destino_viagem')
                    ->label('Quantidade')
                    ->width('1%')
                    ->sortable()
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('integrados_nomes_view')
                    ->label('Integrado')
                    ->width('1%')
                    ->html()
                    ->formatStateUsing(fn(?string $state): string => $state ?: 'Sem Carga Vinculada')
                    ->placeholder('Sem Carga Vinculada')
                    ->tooltip(fn(Models\Viagem $record) => strip_tags($record->integrados_nomes_view ?: ''))
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transp.')
                    ->width('1%')
                    ->disabledClick()
                    ->placeholder('Sem Doc. Transp.')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('documentos_frete_resumo_view')
                    ->label('Fretes')
                    // ->width('1%')
                    ->formatStateUsing(fn(?string $state): string => $state ?: 'Sem Frete')
                    ->html()
                    ->tooltip(fn(Viagem $record) => strip_tags($record->documentos_frete_resumo_view ?: ''))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parceiro_frete_view')
                    ->label('Destinos Frete')
                    ->html()
                    ->placeholder('Sem Frete')
                    ->tooltip(fn(Viagem $record) => strip_tags($record->parceiro_frete_view ?: ''))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                ColumnGroup::make('KM', [
                    TextInputColumn::make('km_rodado')
                        ->type('number')
                        ->rules(['numeric', 'min:0', 'required']),
                    // TextColumn::make('km_rodado')
                    //     ->width('1%')
                    //     ->wrapHeader()
                    //     ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                    //     ->summarize(Sum::make()->label('TT Km Rodado')->numeric(decimalPlaces: 2, locale: 'pt-BR')),
                    TextColumn::make('km_pago')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->summarize(Sum::make()->label('TT Km Pago')->numeric(decimalPlaces: 2, locale: 'pt-BR')),
                    TextInputColumn::make('km_cadastro')
                        ->label('Km Cadastro')
                        ->wrapHeader()
                        ->width('1%')
                        ->type('number')
                        ->sortable()
                        ->disabled(fn(Models\Viagem $record) => $record->conferido)
                        ->rules(['numeric', 'min:0', 'required'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->afterStateUpdated(function ($state, Models\Viagem $record) {
                            if (!$record->relationLoaded('cargas')) {
                                $record->load('cargas.integrado');
                            }

                            $primeiraIntegrado = $record->cargas->first()?->integrado;

                            if ($primeiraIntegrado) {
                                (new Services\IntegradoService)->atualizarKmRota($primeiraIntegrado, $state);
                            }
                        }),
                    TextInputColumn::make('km_cobrar')
                        ->width('1%')
                        ->wrapHeader()
                        ->type('number')
                        ->disabled(fn(Models\Viagem $record) => ($record->conferido && !Auth::user()->is_admin))
                        ->rules(['numeric', 'min:0', 'required'])
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('km_dispersao')
                        ->label('Km Dispersão')
                        ->width('1%')
                        ->color(fn($state, Models\Viagem $record): string => $record->km_dispersao > 3.99 ? 'danger' : 'info')
                        ->badge()
                        ->wrapHeader()
                        ->sortable()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->summarize(Sum::make()->label('TT Km Dispersão')->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('dispersao_percentual')
                        ->label('Dispersão %')
                        ->width('1%')
                        ->suffix('%')
                        ->color(fn($state, Models\Viagem $record): string => $record->dispersao_percentual > 2 ? 'danger' : 'info')
                        ->badge()
                        ->wrapHeader()
                        ->sortable()
                        ->summarize(
                            Summarizer::make()
                                ->label('Dispersão %')
                                ->using(function ($query) {
                                    // calcula (SUM(km_dispersao) / SUM(km_rodado)) * 100, trata divisão por zero
                                    return $query->selectRaw(
                                        <<<'SQL'
                                    CASE
                                        WHEN COALESCE(SUM(km_rodado), 0) = 0 THEN NULL
                                        ELSE (SUM(km_dispersao) / SUM(km_rodado)) * 100
                                    END AS aggregate
                                SQL
                                    )->value('aggregate');
                                })
                                ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        )
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('km_rota_corrigido')
                        ->wrapHeader()
                        ->width('1%')
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->toggleable(isToggledHiddenByDefault: true),
                    SelectColumn::make('motivo_divergencia')
                        ->label('Motivo Divergência')
                        ->wrapHeader()
                        ->grow(false)
                        ->options(Enum\MotivoDivergenciaViagem::toSelectArray())
                        ->default(Enum\MotivoDivergenciaViagem::SEM_OBS->value)
                        ->disabled(fn(Models\Viagem $record) => ($record->conferido && !Auth::user()->is_admin))
                        ->toggleable(isToggledHiddenByDefault: false)
                ]),
                ColumnGroup::make('Datas', [
                    TextInputColumn::make('data_competencia')
                        ->type('date')
                        ->label('Dt. Comp.')
                        ->width('1%')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('data_inicio')
                        ->label('Dt. Início')
                        ->width('1%')
                        ->disabledClick()
                        ->dateTime('d/m/Y H:i')
                        ->sortable(),
                    TextColumn::make('data_fim')
                        ->label('Dt. Fim')
                        ->width('1%')
                        ->disabledClick()
                        ->dateTime('d/m/Y H:i')
                        ->dateTimeTooltip()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false),
                ]),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->width('1%')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado Em')
                    ->width('1%')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('conferido')
                    ->width('1%')
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'info',
                        default => 'danger',
                    }),
                ColumnGroup::make('Users', [
                    TextColumn::make('creator.name')
                        ->label('Criado Por')
                        ->width('1%')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updater.name')
                        ->label('Atualizado Por')
                        ->width('1%')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('checker.name')
                        ->label('Conferido Por')
                        ->width('1%')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
                TextColumn::make('condutor')
                    ->label('Motorista')
                    ->default('Sem Motorista')
                    ->grow(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->grow()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resultado_periodo_id')
                    ->label("Resultado Período ID")
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('resultadoPeriodo.data_inicio')
                    ->label('Resultado Período')
                    ->disabledClick()
                    ->date('d/m/Y')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('documentos_count')
                    ->label('Qtd. Doc. Frete')
                    // ->counts('documentos')
                    ->width('1%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups(
                [
                    Group::make('data_competencia')
                        ->label('Data Competência')
                        ->titlePrefixedWithLabel(false)
                        ->getTitleFromRecordUsing(fn(Models\Viagem $record): string => Carbon::parse($record->data_competencia)->format('d/m/Y'))
                        ->collapsible(),
                    Group::make('veiculo.placa')
                        ->label('Veículo')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                ]
            )
            ->paginated([10, 25, 50, 100, 200])
            ->filters([
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('cargas.integrado', 'nome')
                    ->searchable(['codigo', 'nome'])
                    ->getOptionLabelFromRecordUsing(fn(Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Filter::make('numero_viagem')
                    ->schema([
                        TextInput::make('numero_viagem')
                            ->label('Nº Viagem'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['numero_viagem']) {
                            return null;
                        }

                        return "Nº Viagem: {$data['numero_viagem']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['numero_viagem'],
                                fn(Builder $query, $numeroViagem): Builder => $query->where('numero_viagem', $numeroViagem),
                            );
                    }),
                Filter::make('documento_transporte')
                    ->schema([
                        TextInput::make('documento_transporte')
                            ->label('Nº Doc. Transporte'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['documento_transporte']) {
                            return null;
                        }

                        return "Doc. Transp.: {$data['documento_transporte']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['documento_transporte'],
                                fn(Builder $query, $documentoTransporte): Builder => $query->where('documento_transporte', $documentoTransporte),
                            );
                    }),

                DateRangeFilter::make('data_competencia')
                    ->label('Dt. Competência')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('data_inicio')
                    ->label('Dt. Inicio')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('data_fim')
                    ->label('Dt. Fim')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                TernaryFilter::make('possui_documento')
                    ->label('Possui Doc. Transp.?')
                    ->attribute('documento_transporte')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('C/ Doc. Transp.')
                    ->falseLabel('S/ Doc. Transp.'),
                TernaryFilter::make('sem_carga')
                    ->label('Possui Integrado?')
                    ->placeholder('Todos')
                    ->trueLabel('Com Integrado')
                    ->falseLabel('Sem Integrado')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('cargas', function (Builder $subQuery) {
                            $subQuery->whereNotNull('integrado_id');
                        }),
                        false: fn(Builder $query) => $query->whereHas('cargas', function (Builder $subQuery) {
                            $subQuery->whereNull('integrado_id');
                        })->orWhereDoesntHave('cargas'),
                        blank: fn(Builder $query) => $query,
                    ),
                TernaryFilter::make('sem_frete')
                    ->label('Possui Doc. Frete?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Com Doc. Frete')
                    ->falseLabel('Sem Doc. Frete')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('documentos'),
                        false: fn(Builder $query) => $query->whereDoesntHave('documentos'),
                        blank: fn(Builder $query) => $query,
                    ),
                TernaryFilter::make('conferido')
                    ->label('Conferido')
                    ->trueLabel('Sim')
                    ->falseLabel('Não'),
                SelectFilter::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->options(Enum\MotivoDivergenciaViagem::toSelectArray())
                    ->multiple()
                    ->columnSpanFull(),
                Filter::make('integrados_count')
                    ->label('Qtd. Integrados')
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->compact()
                            ->collapsible()
                            ->collapsed()
                            ->description('Quantidade de integrados vinculados à viagem.')
                            ->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                Select::make('operator')
                                    ->label('Operador')
                                    ->columnSpan(2)
                                    ->options([
                                        '>=' => 'Maior ou igual',
                                        '<=' => 'Menor ou igual',
                                        '='  => 'Igual',
                                        '>'  => 'Maior que',
                                        '<'  => 'Menor que',
                                    ])
                                    ->default('>='),
                                TextInput::make('count')
                                    ->label('Quantidade')
                                    ->columnSpan(2)
                                    ->type('number'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $count = (int) ($data['count'] ?? 0);
                        $op = $data['operator'] ?? '>=';

                        if ($count <= 0) {
                            return $query;
                        }

                        // usa subquery para contar integrados distintos por viagem e aplicar having via whereRaw
                        // garante compatibilidade independentemente de relacionamentos Eloquent
                        $binding = [$count];
                        $raw = "(select count(distinct cv.integrado_id) from cargas_viagem cv where cv.viagem_id = viagens.id) {$op} ?";

                        return $query->whereRaw($raw, $binding);
                    }),
                Filter::make('range_dispersao')
                    ->label('Range Dispersão Km')
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->compact()
                            ->collapsible()
                            ->collapsed()
                            ->description('Range de Km de Dispersão das cargas vinculadas à viagem.')
                            ->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                TextInput::make('minimo')
                                    ->label('Mínimo')
                                    ->columnSpan(2)
                                    ->type('number'),
                                TextInput::make('maximo')
                                    ->label('Máximo')
                                    ->columnSpan(2)
                                    ->type('number'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        //verificar se possui valor e aplicar filtros
                        $min = $data['minimo'] ?? null;
                        $max = $data['maximo'] ?? null;
                        return $query
                            ->when($min !== null, fn(Builder $query, $min): Builder => $query->where('km_dispersao', '>=', $min))
                            ->when($max !== null, fn(Builder $query, $max): Builder => $query->where('km_dispersao', '<=', $max));
                    }),
                SelectFilter::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->options([
                        'CHAPECO'       => 'Chapecó',
                        'CATANDUVAS'    => 'Catanduvas',
                        'CONCORDIA'     => 'Concórdia',
                    ])
                    ->default('CHAPECO'),
                SelectFilter::make('cliente')
                    ->label('Cliente')
                    ->options(Enum\ClienteEnum::toSelectArray())
                    ->multiple(),
                RegistrosSemVinculoResultadoFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtros')
                    ->slideOver(),
            )
            ->reorderableColumns()
            ->defaultGroup('data_competencia')
            ->defaultSort('numero_viagem')
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferFilters()
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(false)
            ->selectable()
            ->recordActions([
                ActionGroup::make([
                    Action::make('sem-viagem')
                        ->label('Sem Viagem')
                        ->icon('heroicon-o-x-circle')
                        ->accessSelectedRecords()
                        ->action(function (Collection $selectedRecords) {
                            $selectedRecords->each(function (Models\Viagem $record) {
                                $record->update([
                                    'motivo_divergencia' => Enum\MotivoDivergenciaViagem::SEM_VIAGEM->value,
                                    'conferido' => true,
                                ]);
                                $record->carga()->create([
                                    'integrado_id' => 517,  //BRF CCO
                                    'created_by' => Auth::id(),
                                    'updated_by' => Auth::id(),
                                ]);
                            });
                        })
                        ->hidden(fn(Models\Viagem $record): bool => $record->cargas_count > 0 || $record->documentos_count > 0)
                        ->color('danger'),
                    Viagems\Actions\AdicionarComentarioAction::make(),
                    Viagems\Actions\VisualizarComentarioAction::make(),
                    EditAction::make()
                        ->visible(fn(Models\Viagem $record) => ! $record->conferido || Auth::user()->is_admin)
                        ->after(fn(Models\Viagem $record) => (new Services\ViagemService())->recalcularViagem($record)),
                    Action::make('buscar_documentos')
                        ->label('Buscar Documentos')
                        ->icon('heroicon-o-document-magnifying-glass')

                        ->url(fn(Models\Viagem $record) => DocumentoFretes\DocumentoFreteResource::getUrl('index', [
                            'filters' => [
                                'veiculo_id' => [
                                    'values' => [
                                        0 => $record->veiculo_id,
                                    ]
                                ],
                                'sem_vinculo_viagem' => [
                                    'isActive' => true
                                ]
                            ],
                        ]))
                        ->openUrlInNewTab()
                        ->color('info'),
                    Action::make('directions')
                        ->label('Direções')
                        ->icon('heroicon-o-map')
                        ->action(function (Models\Viagem $record) {
                            $url = $record->maps_integrados['directions_url'] ?? null;
                            if ($url) {
                                redirect()->away($url); // 'away' for external URLs
                            }
                        })
                        ->visible(fn(Models\Viagem $record): bool => $record->cargas_count > 0),
                    ReplicateAction::make()
                        ->label('Duplicar')
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['created_by'] = Auth::id();
                            $data['updated_by'] = Auth::id();
                            $data['conferido'] = false;
                            $data['numero_viagem'] = $data['numero_viagem'] . '-B';
                            $data['updated_by'] = Auth::id();

                            return $data;
                        })
                        ->fillForm(fn(Viagem $record) => [
                            'veiculo_id'            => $record->veiculo_id,
                            'unidade_negocio'       => $record->unidade_negocio,
                            'numero_viagem'         => $record->numero_viagem,
                            'data_aquisicao'        => $record->data_aquisicao,
                            'considerar_relatorio'  => $record->considerar_relatorio,
                            'data_competencia'      => $record->data_competencia,
                            'data_inicio'           => $record->data_inicio,
                            'data_fim'              => $record->data_fim,
                            'km_rodado'             => $record->km_rodado,
                            'km_pago'               => $record->km_pago,
                            'km_cobrar'             => $record->km_cobrar,
                            'km_cadastro'           => $record->km_cadastro,
                            'motivo_divergencia'    => $record->motivo_divergencia,
                        ])
                        ->schema(fn(Schema $schema) => ViagemResource::form($schema))
                        ->successNotificationTitle('Viagem Duplicada')
                        ->excludeAttributes([
                            'id',
                            'documento_transporte',
                            'conferido',
                            'divergencias',
                            'created_at',
                            'updated_at',
                            'created_by',
                            'updated_by',
                            'checked_by',
                            'integrados_nomes',
                            'km_dispersao',
                            'dispersao_percentual',
                            'km_rota_corrigido',
                            'documentos_count',
                            'comentarios_exists',
                            'cargas_count',
                        ]),
                    DeleteAction::make(),
                ])->link()
                    ->dropdownPlacement('top-start'),
                Viagems\Actions\NovaCargaAction::make(),
                Viagems\Actions\ViagemConferidaAction::make(),
                Viagems\Actions\ViagemNaoConferidaAction::make(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->headerActions([
                Viagems\Actions\ImportDocumentosAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                Viagems\Actions\VincularViagemDocumentoBulkAction::make(),
                Viagems\Actions\RegistrarComplementoViagem::make(),
                Viagems\Actions\MarcarViagemConferidaAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()->is_admin),
                    DissociateResultadoPeriodoBulkAction::make(),
                    FilamentExportBulkAction::make('export')
                ]),
            ]);
    }
}
