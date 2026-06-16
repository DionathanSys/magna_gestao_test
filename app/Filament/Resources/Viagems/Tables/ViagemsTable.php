<?php

namespace App\Filament\Resources\Viagems\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\Enum;
use App\Filament\Actions\DissociateResultadoPeriodoBulkAction;
use App\Filament\Actions\ExportPdfBulkAction;
use App\Filament\Components\RegistrosSemVinculoResultadoFilter;
use App\Filament\Resources\DocumentoFretes;
use App\Filament\Resources\Viagems;
use App\Filament\Resources\Viagems\Actions\VincularViagemResultadoPeriodoBulkAction;
use App\Filament\Resources\Viagems\ViagemResource;
use App\Models;
use App\Models\CteEmailRequest;
use App\Models\Viagem;
use App\Services;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ViagemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->select('viagens.*')
                    ->selectSub(
                        CteEmailRequest::query()
                            ->select('status')
                            ->whereColumn('viagem_id', 'viagens.id')
                            ->latest('id')
                            ->limit(1),
                        'cte_email_request_status'
                    )
                    ->selectSub(
                        CteEmailRequest::query()
                            ->select('requested_at')
                            ->whereColumn('viagem_id', 'viagens.id')
                            ->latest('id')
                            ->limit(1),
                        'cte_email_requested_at'
                    )
                    ->with([
                        'veiculo:id,placa',
                        'checker:id,name',
                        'creator:id,name',
                        'updater:id,name',
                        'resultadoPeriodo:id,data_inicio',
                    ])
                    ->withCount(['cargas', 'documentos', 'cteEmailRequests']);
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
                TextColumn::make('numero_interno')
                    ->label('Nº Interno')
                    ->width('1%')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->disabledClick(),
                TextColumn::make('total_destinos')
                    ->label('Quantidade')
                    ->width('1%')
                    ->sortable()
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('integrados_nomes_view')
                    ->label('Integrado')
                    ->width('1%')
                    ->html()
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Sem Carga Vinculada')
                    ->placeholder('Sem Carga Vinculada')
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transp.')
                    ->width('1%')
                    ->disabledClick()
                    ->placeholder('Sem Doc. Transp.')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('cte_email_request_status')
                    ->label('Solic. CTe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending_send' => 'Pendente envio',
                        'sent' => 'Enviado',
                        'response_received' => 'Resposta recebida',
                        'processing' => 'Processando',
                        'completed' => 'Concluida',
                        'failed' => 'Falhou',
                        default => 'Nao solicitada',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending_send' => 'warning',
                        'sent' => 'info',
                        'response_received' => 'primary',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (Viagem $record): ?string => $record->cte_email_requested_at
                        ? 'Solicitado em '.Carbon::parse($record->cte_email_requested_at)->format('d/m/Y H:i')
                        : null)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('documentos_frete_resumo_cache')
                    ->label('Fretes')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Sem Frete')
                    ->html()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    TextColumn::make('km_dispersao')
                        ->label('Km Dispersão')
                        ->width('1%')
                        ->color(fn ($state, Viagem $record): string => $record->km_dispersao > 3.99 ? 'danger' : 'info')
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
                        ->color(fn ($state, Viagem $record): string => $record->dispersao_percentual > 2 ? 'danger' : 'info')
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
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'info',
                        default => 'danger',
                    }),
                IconColumn::make('possui_pendencia')
                    ->label('Pendência')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('ignorar')
                    ->label('Ignorar')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                TextColumn::make('motorista1')
                    ->label('Motorista 1')
                    ->default('Sem Motorista')
                    ->grow(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->grow()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resultado_periodo_id')
                    ->label('Resultado Período ID')
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
                TextColumn::make('pendencias_resumo')
                    ->label('Pendências')
                    ->wrap()
                    ->badge()
                    ->color(fn (Viagem $record): string => $record->possui_pendencia ? 'warning' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups(
                [
                    Group::make('data_competencia')
                        ->label('Data Competência')
                        ->titlePrefixedWithLabel(false)
                        ->getTitleFromRecordUsing(fn (Viagem $record): string => Carbon::parse($record->data_competencia)->format('d/m/Y'))
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
                    ->getOptionLabelFromRecordUsing(fn (Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
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
                                fn (Builder $query, $numeroViagem): Builder => $query->where('numero_viagem', $numeroViagem),
                            );
                    }),
                Filter::make('numero_interno')
                    ->schema([
                        TextInput::make('numero_interno')
                            ->label('Nº Viagem Interno'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['numero_interno']) {
                            return null;
                        }

                        return "Nº Interno: {$data['numero_interno']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['numero_interno'],
                                fn (Builder $query, $numeroInterno): Builder => $query->where('numero_interno', $numeroInterno),
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
                                fn (Builder $query, $documentoTransporte): Builder => $query->where('documento_transporte', $documentoTransporte),
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
                        true: fn (Builder $query) => $query->whereHas('cargas', function (Builder $subQuery) {
                            $subQuery->whereNotNull('integrado_id');
                        }),
                        false: fn (Builder $query) => $query->where(function (Builder $subQuery) {
                            $subQuery
                                ->whereHas('cargas', function (Builder $innerQuery) {
                                    $innerQuery->whereNull('integrado_id');
                                })
                                ->orWhereDoesntHave('cargas');
                        }),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('sem_frete')
                    ->label('Possui Doc. Frete?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Com Doc. Frete')
                    ->falseLabel('Sem Doc. Frete')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('documentos'),
                        false: fn (Builder $query) => $query->whereDoesntHave('documentos'),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('conferido')
                    ->label('Conferido')
                    ->trueLabel('Sim')
                    ->falseLabel('Não'),
                TernaryFilter::make('possui_pendencia')
                    ->label('Possui Pendência')
                    ->trueLabel('Sim')
                    ->falseLabel('Não'),
                TernaryFilter::make('ignorar')
                    ->label('Ignorar Viagem')
                    ->trueLabel('Sim')
                    ->falseLabel('Não'),
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
                                        '=' => 'Igual',
                                        '>' => 'Maior que',
                                        '<' => 'Menor que',
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

                        return $query->has('cargas', $op, $count);
                    }),
                SelectFilter::make('tipo_pendencia')
                    ->label('Tipo de Pendência')
                    ->options([
                        'multiplos_destinos' => 'Múltiplos destinos',
                        'sem_km_pago' => 'Sem km pago',
                        'sem_km_rodado' => 'Sem km rodado',
                        'km_acima_limite' => 'Km acima do limite',
                        'sem_integrado' => 'Sem integrado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $tipo = $data['value'] ?? null;

                        if (! $tipo) {
                            return $query;
                        }

                        return $query->whereNotNull("pendencias->{$tipo}");
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
                        // verificar se possui valor e aplicar filtros
                        $min = $data['minimo'] ?? null;
                        $max = $data['maximo'] ?? null;

                        return $query
                            ->when($min !== null, fn (Builder $query, $min): Builder => $query->where('km_dispersao', '>=', $min))
                            ->when($max !== null, fn (Builder $query, $max): Builder => $query->where('km_dispersao', '<=', $max));
                    }),
                SelectFilter::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->options([
                        'CHAPECO' => 'Chapecó',
                        'CATANDUVAS' => 'Catanduvas',
                        'CONCORDIA' => 'Concórdia',
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
                fn (Action $action) => $action
                    ->button()
                    ->label('Filtros')
                    ->slideOver(),
            )
            ->columnManagerColumns(4)
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->columnManagerWidth(Width::ScreenTwoExtraLarge)
            ->columnManagerMaxHeight('80vh')
            ->columnManagerTriggerAction(
                fn (Action $action) => $action
                    ->modalWidth(Width::ScreenTwoExtraLarge)
            )
            ->reorderableColumns()
            ->defaultSort('numero_viagem')
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferFilters()
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(false)
            ->selectable()
            ->recordActions([
                ActionGroup::make([
                    Action::make('atualizar')
                        ->label('Atualizar')
                        ->icon(Heroicon::ArrowLeftCircle)
                        ->action(function () {
                            // Não faz nada no backend - evita refresh imediato
                        })

                        ->color('primary'),
                    Action::make('sem-viagem')
                        ->label('Sem Viagem')
                        ->icon('heroicon-o-x-circle')
                        ->accessSelectedRecords()
                        ->action(function (Collection $selectedRecords) {
                            Log::debug('Iniciando ação em massa Sem Viagem para '.$selectedRecords->count().' registros pelo usuário ID '.Auth::id());
                            $selectedRecords->each(function (Viagem $record) {
                                Log::debug("Processando Viagem ID {$record->id} na ação Sem Viagem pelo usuário ID ".Auth::id());
                                $record->update([
                                    'possui_pendencia' => false,
                                    'pendencias' => [],
                                    'conferido' => true,
                                ]);
                                $record->carga()->create([
                                    'integrado_id' => 517,  // BRF CCO
                                    'created_by' => Auth::id(),
                                    'updated_by' => Auth::id(),
                                ]);
                                Log::info("Viagem ID {$record->id} marcada como SEM VIAGEM e vinculada ao integrado BRF CCO pelo usuário ID ".Auth::id());
                            });
                        })
                        ->hidden(fn (Viagem $record): bool => $record->cargas_count > 0 || $record->documentos_count > 0)
                        ->color('danger'),
                    Viagems\Actions\AdicionarComentarioAction::make(),
                    Viagems\Actions\VisualizarComentarioAction::make(),
                    EditAction::make()
                        ->visible(fn (Viagem $record) => ! $record->conferido || Auth::user()->is_admin)
                    // ->after(fn(Models\Viagem $record) => (new Services\ViagemService())->recalcularViagem($record))
                    ,
                    Action::make('buscar_documentos')
                        ->label('Buscar Documentos')
                        ->icon('heroicon-o-document-magnifying-glass')

                        ->url(fn (Viagem $record) => DocumentoFretes\DocumentoFreteResource::getUrl('index', [
                            'filters' => [
                                'veiculo_id' => [
                                    'values' => [
                                        0 => $record->veiculo_id,
                                    ],
                                ],
                                'sem_vinculo_viagem' => [
                                    'isActive' => true,
                                ],
                            ],
                        ]))
                        ->openUrlInNewTab()
                        ->color('info'),
                    Action::make('directions')
                        ->label('Direções')
                        ->icon('heroicon-o-map')
                        ->url(fn (Viagem $record) => $record->maps_integrados['directions_url'] ?? null)
                        ->openUrlInNewTab()
                        ->visible(fn (Viagem $record): bool => $record->cargas_count > 0),
                    Viagems\Actions\SolicitarCteBugioAction::make(),
                    ReplicateAction::make()
                        ->label('Duplicar')
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['created_by'] = Auth::id();
                            $data['updated_by'] = Auth::id();
                            $data['conferido'] = false;
                            $data['numero_viagem'] = $data['numero_viagem'].'-B';
                            $data['updated_by'] = Auth::id();

                            return $data;
                        })
                        ->fillForm(fn (Viagem $record) => [
                            'veiculo_id' => $record->veiculo_id,
                            'unidade_negocio' => $record->unidade_negocio,
                            'numero_viagem' => $record->numero_viagem,
                            'data_aquisicao' => $record->data_aquisicao,
                            'data_competencia' => $record->data_competencia,
                            'data_inicio' => $record->data_inicio,
                            'data_fim' => $record->data_fim,
                            'km_rodado' => $record->km_rodado,
                            'km_pago' => $record->km_pago,
                            'total_destinos' => $record->total_destinos,
                            'ignorar' => $record->ignorar,
                            'motorista1' => $record->motorista1,
                            'motorista2' => $record->motorista2,
                        ])
                        ->schema(fn (Schema $schema) => ViagemResource::form($schema))
                        ->successNotificationTitle('Viagem Duplicada')
                        ->excludeAttributes([
                            'id',
                            'documento_transporte',
                            'conferido',
                            'pendencias',
                            'created_at',
                            'updated_at',
                            'created_by',
                            'updated_by',
                            'checked_by',
                            'integrados_nomes',
                            'km_dispersao',
                            'dispersao_percentual',
                            'documentos_count',
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
                Viagems\Actions\MarcarViagemConferidaAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->is_admin),
                    DissociateResultadoPeriodoBulkAction::make(),
                    VincularViagemResultadoPeriodoBulkAction::make(),
                    Viagems\Actions\VincularViagemDocumentoBulkAction::make(),
                    Viagems\Actions\ExportarViagensExcelBulkAction::make(),
                    Viagems\Actions\ExportarRelatorioViagensDocumentosBulkAction::make(),
                    FilamentExportBulkAction::make('export'),
                    ExportPdfBulkAction::make(
                        'exportar_pdf',
                        'Viagens',
                        [
                            ['key' => 'id', 'label' => 'ID', 'align' => 'center', 'width' => '5%'],
                            ['key' => 'numero_viagem', 'label' => 'N° Viagem', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'doc_transporte', 'label' => 'Doc. Transporte', 'align' => 'center', 'width' => '12%'],
                            ['key' => 'placa', 'label' => 'Placa', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'width' => '12%'],
                            ['key' => 'data_competencia', 'label' => 'Data Competencia', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'km_pago', 'label' => 'Km Pago', 'align' => 'right', 'width' => '8%'],
                            ['key' => 'total_destinos', 'label' => 'Destinos', 'align' => 'center', 'width' => '6%'],
                            ['key' => 'conferido', 'label' => 'Conferido', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'status', 'label' => 'Status', 'width' => '12%'],
                        ],
                        fn ($records) => $records->load('veiculo')
                            ->map(fn ($r) => [
                                'id' => $r->id,
                                'numero_viagem' => e($r->numero_viagem ?? '-'),
                                'doc_transporte' => e($r->documento_transporte ?? '-'),
                                'placa' => e($r->veiculo?->placa ?? '-'),
                                'cliente' => e($r->cliente ?? '-'),
                                'data_competencia' => $r->data_competencia
                                    ? Carbon::parse($r->data_competencia)->format('d/m/Y')
                                    : '-',
                                'km_pago' => $r->km_pago ?? '-',
                                'total_destinos' => $r->total_destinos ?? '-',
                                'conferido' => $r->conferido ? 'Sim' : 'Nao',
                                'status' => e($r->status ?? '-'),
                            ])->toArray(),
                    ),
                ]),
            ]);
    }
}
