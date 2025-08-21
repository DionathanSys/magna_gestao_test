<?php

namespace App\Filament\Resources\Viagems\Tables;

use Filament\Actions\{ActionGroup, BulkActionGroup, CreateAction, DeleteBulkAction, EditAction, ImportAction, };
use Filament\Tables\Columns\{ColumnGroup, IconColumn, SelectColumn, StaticAction, TextColumn, TextInputColumn, };
use Filament\Tables\Table;
use App\Models;
use App\Services;
use App\Enum;
use App\Filament\Imports\ViagemImporter;
use App\Filament\Resources\Viagems;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\{Filter, SelectFilter, TernaryFilter};
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificacaoService as notify;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\RecordActionsPosition;

class ViagemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                    $query->with('carga.integrado', 'veiculo');
                })
            ->poll(null)
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('numero_viagem')
                    ->label('Nº Viagem')
                    ->width('1%')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),
                TextColumn::make('cargas.integrado.codigo')
                    ->label('Cód. Integrado')
                    ->width('1%')
                    ->default('Sem Integrado')
                    ->listWithLineBreaks()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cargas.integrado.nome')
                    ->label('Integrado')
                    ->width('1%')
                    ->tooltip(fn(Models\Viagem $record) => $record->carga->integrado?->codigo ?? 'N/A')
                    ->listWithLineBreaks()
                    ->disabledClick(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transp.')
                    ->width('1%')
                    ->disabledClick()
                    ->default('Sem Doc. Transp.')
                    ->toggleable(isToggledHiddenByDefault: true),
                ColumnGroup::make('KM', [
                    TextColumn::make('km_rodado')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR')),
                    TextColumn::make('km_pago')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR')),
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
                    TextColumn::make('km_dispersao')
                        ->label('Km Dispersão')
                        ->width('1%')
                        ->color(fn($state, Models\Viagem $record): string => $record->km_dispersao > 3.49 ? 'danger' : 'info')
                        ->badge()
                        ->wrapHeader()
                        ->sortable()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('dispersao_percentual')
                        ->label('Dispersão %')
                        ->width('1%')
                        ->suffix('%')
                        ->badge()
                        ->wrapHeader()
                        ->sortable()
                        ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextInputColumn::make('km_cobrar')
                        ->width('1%')
                        ->wrapHeader()
                        ->type('number')
                        ->disabled(fn(Models\Viagem $record) => ($record->conferido && !Auth::user()->is_admin))
                        ->rules(['numeric', 'min:0', 'required'])
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('km_rota_corrigido')
                        ->wrapHeader()
                        ->width('1%')
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR')
                        ->toggleable(isToggledHiddenByDefault: true),
                    SelectColumn::make('motivo_divergencia')
                        ->label('Motivo Divergência')
                        ->native(false)
                        ->wrapHeader()
                        ->grow()
                        // ->width('2%')
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
                        ->dateTime('d/m/Y H:i')
                        ->sortable(),
                    TextColumn::make('data_fim')
                        ->label('Dt. Fim')
                        ->width('1%')
                        ->dateTime('d/m/Y H:i')
                        ->dateTimeTooltip()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false),
                ]),
                IconColumn::make('conferido')
                    ->width('1%')
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'info',
                        default => 'danger',
                    }),
                TextColumn::make('complementos_exists')
                    ->label('Complementos')
                    ->width('1%')
                    ->exists('complementos')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ])
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
                TernaryFilter::make('conferido')
                    ->label('Conferido')
                    ->trueLabel('Sim')
                    ->falseLabel('Não'),
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('cargas.integrado', 'nome')
                    ->searchable(['codigo', 'nome'])
                    ->getOptionLabelFromRecordUsing(fn(Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Filter::make('numero_viagem')
                    ->schema([
                        TextInput::make('numero_viagem')
                            ->label('Nº Viagem'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['numero_viagem'],
                                fn(Builder $query, $numeroViagem): Builder => $query->where('numero_viagem', $numeroViagem),
                            );
                    }),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->columnSpanFull(),
                Filter::make('data_competencia')
                    ->schema([
                        DatePicker::make('data_inicio')
                            ->label('Data Comp. Início'),
                        DatePicker::make('data_fim')
                            ->label('Data Comp. Fim'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_inicio'],
                                fn(Builder $query, $date): Builder => $query->whereDate('data_competencia', '>=', $date),
                            )
                            ->when(
                                $data['data_fim'],
                                fn(Builder $query, $date): Builder => $query->whereDate('data_competencia', '<=', $date),
                            );
                    }),
                SelectFilter::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->options(Enum\MotivoDivergenciaViagem::toSelectArray())
                    ->multiple()
                    ->columnSpanFull(),
            ])
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
                    EditAction::make()
                        ->visible(fn(Models\Viagem $record) => ! $record->conferido)
                        ->after(fn(Models\Viagem $record) => (new Services\ViagemService())->recalcularViagem($record)),
                    DeleteAction::make(),
                ])->link()
                ->dropdownPlacement('top-start'),
                Viagems\Actions\NovaCargaAction::make(),
                Viagems\Actions\ViagemConferidaAction::make(),
                Viagems\Actions\ViagemNaoConferidaAction::make(),
                ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                CreateAction::make(),
                Viagems\Actions\RegistrarComplementoViagem::make(),
                DeleteBulkAction::make(),



            ]);
    }
}
