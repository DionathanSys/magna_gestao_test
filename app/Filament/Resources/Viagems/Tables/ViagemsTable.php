<?php

namespace App\Filament\Resources\Viagems\Tables;

use Filament\Actions\{ActionGroup, BulkActionGroup, CreateAction, DeleteBulkAction, EditAction, ImportAction,};
use Filament\Tables\Columns\{ColumnGroup, IconColumn, SelectColumn, StaticAction, TextColumn, TextInputColumn,};
use Filament\Tables\Table;
use App\{Models, Services, Enum};
use App\Filament\Resources\{DocumentoFretes, Viagems};
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\{Filter, Indicator, QueryBuilder, SelectFilter, TernaryFilter};
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Enums\RecordActionsPosition;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ViagemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'carga.integrado:id,nome,municipio',
                    'veiculo:id,placa',
                    'comentarios',
                    'checker:id,name',
                    'creator:id,name',
                    'updater:id,name',
                ])
                    // antecipa o cálculo de existência para evitar N+1
                    ->withExists(['comentarios']);
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
                // TextColumn::make('integrados_nomes')
                //     ->label('Integrado')
                //     ->width('1%')
                //     ->html()
                //     ->tooltip(fn(Models\Viagem $record) => $record->integrados_nomes)
                //     ->disabledClick(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transp.')
                    ->width('1%')
                    ->disabledClick()
                    ->default('Sem Doc. Transp.')
                    ->toggleable(isToggledHiddenByDefault: false),
                ColumnGroup::make('KM', [
                    TextColumn::make('km_rodado')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->summarize(Sum::make()->label('TT Km Rodado')->numeric(decimalPlaces: 2, locale: 'pt-BR')),
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
                        ->afterStateUpdated(fn($state, Models\Viagem $record) => (new Services\IntegradoService)->atualizarKmRota(
                            Models\Integrado::find($record->carga->integrado_id),
                            $state
                        )),
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
            ->recordActions([
                ActionGroup::make([
                    Action::make('atualizar')
                        ->label('Atualizar')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Models\Viagem $record) {}),
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
                        ->url(fn(Models\Viagem $record): ?string => $record->maps_integrados['directions_url'] ?? null)
                        ->openUrlInNewTab()
                        ->visible(fn(Models\Viagem $record): bool => ! empty($record->maps_integrados)),
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
                DeleteBulkAction::make(),
            ]);
    }
}
