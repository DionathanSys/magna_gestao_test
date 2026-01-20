<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\MotivoDivergenciaViagem;
use App\Filament\Resources\DocumentoFretes\DocumentoFreteResource;
use App\Filament\Resources\Viagems\Actions\AdicionarComentarioAction;
use App\Filament\Resources\Viagems\Actions\VisualizarComentarioAction;
use App\Filament\Resources\Viagems\ViagemResource;
use App\Models;
use App\Models\Viagem;
use App\Services\ViagemService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ViagensRelationManager extends RelationManager
{
    protected static string $relationship = 'viagens';

    public function form(Schema $schema): Schema
    {
        return ViagemResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'cargas' => function ($query) {
                        $query->select(['id', 'viagem_id', 'integrado_id', 'km_dispersao', 'km_dispersao_rateio'])
                            ->with('integrado:id,nome,municipio,codigo,latitude,longitude');
                    },
                    'veiculo:id,placa',
                    'comentarios.creator:id,name',
                    'checker:id,name',
                    'creator:id,name',
                    'updater:id,name',
                ])
                    // antecipa o cálculo de existência para evitar N+1
                    ->withExists(['comentarios'])
                    ->withCount(['cargas', 'documentos']);
            })
            ->recordTitleAttribute('resultado_periodo_id')
            ->columns([
                TextColumn::make('numero_viagem')
                    ->label('Nº Viagem')
                    ->disabledClick()
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transporte')
                    ->width('1%')
                    ->disabledClick()
                    ->searchable(),
                TextColumn::make('integrados_nomes')
                    ->label('Integrado')
                    ->width('1%')
                    ->html()
                    ->tooltip(fn(Models\Viagem $record) => strip_tags($record->integrados_nomes))
                    ->disabledClick(),
                TextColumn::make('documentos_frete_resumo')
                    ->label('Fretes')
                    ->html()
                    ->tooltip(fn(Viagem $record) => strip_tags($record->documentos_frete_resumo))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('km_rodado')
                    ->label('Km Rodado')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Rodado')
                    )
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->label('Km Pago')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Pago')
                    )
                    ->sortable(),
                TextColumn::make('km_dispersao')
                    ->label('Km Dispersão')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->color(fn($state, Models\Viagem $record): string => $record->km_dispersao > 3.99 ? 'danger' : 'info')
                    ->badge()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Dispersão')
                    ),
                TextColumn::make('dispersao_percentual')
                    ->label('Dispersão Percentual')
                    ->width('1%')
                    ->color(fn($state, Models\Viagem $record): string => $record->dispersao_percentual > 2 ? 'danger' : 'info')
                    ->badge()
                    ->suffix('%')
                    ->numeric(2, ',', '.')
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('km_cobrar')
                    ->label('Km Cobrar')
                    ->width('1%')
                    ->numeric(2, ',', '.')
                    ->summarize(
                        Sum::make()
                            ->numeric(2, ',', '.')
                            ->label('Total Km Cobrar')
                    )
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->width('1%')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('data_competencia')
                    ->label('Dt. Competência')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('data_inicio')
                    ->label('Dt. Início')
                    ->width('1%')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('data_fim')
                    ->label('Dt. Fim')
                    ->width('1%')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('conferido')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updater.name')
                    ->label('Atualizado por')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checker.name')
                    ->label('Verificado por')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('condutor')
                    ->label('Condutor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('considerar_relatorio')
                    ->label('Considerar Relatório')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_inicio')
            ->persistSortInSession()
            ->defaultSortOptionLabel('created_at')
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession()
            ->paginated([25, 50, 100, 250, 500])
            ->extremePaginationLinks()
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn($query) => $query
                            ->whereNull('resultado_periodo_id')
                            ->where('veiculo_id', $this->ownerRecord->veiculo_id)
                            ->orderBy('data_competencia', 'desc')
                    )
                    ->recordTitle(
                        fn($record) =>
                        "#{$record->id} | " .
                            Carbon::parse($record->data_competencia)->format('d/m/Y') . " | Nº " .
                            number_format($record->numero_viagem, 0, ',', '.')
                    )
                    ->multiple()
                    ->recordSelectSearchColumns(['id', 'numero_viagem', 'documento_transporte'])
                    ->label('Vincular Viagens'),
            ])
            ->recordActions([
                ActionGroup::make([
                    AdicionarComentarioAction::make(),
                    VisualizarComentarioAction::make(),
                    EditAction::make()
                        ->visible(fn(Models\Viagem $record) => ! $record->conferido || Auth::user()->is_admin)
                        ->after(fn(Models\Viagem $record) => (new ViagemService())->recalcularViagem($record)),
                    Action::make('buscar_documentos')
                        ->label('Buscar Documentos')
                        ->icon('heroicon-o-document-magnifying-glass')

                        ->url(fn(Models\Viagem $record) => DocumentoFreteResource::getUrl('index', [
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
                    DissociateAction::make(),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                ]),
            ]);
    }
}
