<?php

namespace App\Filament\Resources\CargaViagems\Tables;

use App\Filament\Resources\Viagems\ViagemResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class CargaViagemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll(null)
            ->modifyQueryUsing(function (Builder $query): Builder {
                    return $query->with([
                        'viagem.veiculo:id,placa',
                        'viagem:id,numero_viagem,data_competencia,km_rodado,km_pago,km_cadastro,km_rodado_excedente,km_cobrar,motivo_divergencia,conferido',
                        'integrado:id,nome,codigo,km_rota',
                    ]);
                })
            ->columns([
                TextColumn::make('viagem.veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->wrapHeader()
                    ->sortable(),
                TextColumn::make('viagem.data_competencia')
                    ->label('Dt. Comp.')
                    ->dateTime('d/m/Y')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('viagem.numero_viagem')
                    ->label('Nº Viagem')
                    ->width('1%')
                    ->numeric(0, '', '')
                    ->url(fn(Models\CargaViagem $record): string => ViagemResource::getUrl('view', ['record' => $record->viagem_id]))
                    ->openUrlInNewTab()
                    ->searchable(isIndividual: true)
                    ->sortable(),
                TextColumn::make('integrado.codigo')
                    ->label('Cód. Integrado')
                    ->wrapHeader()
                    ->width('1%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    ColumnGroup::make('Integrado', [
                TextColumn::make('integrado.nome')
                    ->label('Integrado')
                    ->width('1%')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('integrado.km_rota')
                    ->label('Km Rota Integrado')
                    ->width('1%')
                    ->numeric()
                    ->wrapHeader()
                    ->sortable(),]),
                TextColumn::make('Doc. Frete')
                    ->width('1%')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ColumnGroup::make('KM Viagem', [
                    TextColumn::make('viagem.km_rodado')
                        ->label('Km Rodado')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR'),
                    TextColumn::make('viagem.km_pago')
                        ->label('Km Pago')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR'),
                    TextColumn::make('viagem.km_cadastro')
                        ->label('Km Cadastro')
                        ->width('1%')
                        ->wrapHeader()
                        ->numeric(decimalPlaces: 2, locale: 'pt-BR'),
                    TextColumn::make('km_dispersao')
                        ->label('Km Dispersão')
                        ->width('1%')
                        ->wrapHeader()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('km_dispersao_rateio')
                        ->label('Km Rateio')
                        ->width('1%')
                        // ->state(fn($state) => $state ? 'Sim' : 'Não')
                        ->wrapHeader()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('viagem.motivo_divergencia')
                        ->label('Motivo Divergência')
                        ->width('2%')
                        ->formatStateUsing(fn($state) => $state?->value ?? '')
                        ->wrapHeader(),
                    IconColumn::make('viagem.conferido')
                        ->label('Conferido')
                        ->boolean(),
                ]),
                TextColumn::make('viagem.comentarios.conteudo')
                    ->label('Comentários')
                    ->html()
                    ->wrap()
                    ->size(TextSize::ExtraSmall)
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups(
                [
                    Group::make('viagem.numero_viagem')
                        ->label('Nº Viagem')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                    Group::make('viagem.data_competencia')
                        ->label('Data Competência')
                        ->titlePrefixedWithLabel(false)
                        ->getTitleFromRecordUsing(fn(Models\CargaViagem $record): string => Carbon::parse($record->data_competencia)->format('d/m/Y'))
                        ->collapsible(),
                    Group::make('viagem.veiculo.placa')
                        ->label('Veículo')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                    Group::make('integrado.nome')
                        ->label('Integrado')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                    Group::make('viagem.motivo_divergencia')
                        ->label('Motivo Divergência')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                ]
            )
            ->deferFilters()
            ->searchOnBlur()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->filters([
                Filter::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->schema([
                        Select::make('motivo_divergencia')
                            ->label('Motivo Divergência')
                            ->options(\App\Enum\MotivoDivergenciaViagem::toSelectArray())
                            ->searchable()
                            ->preload()
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['motivo_divergencia']),
                            fn($query) =>
                            $query->whereHas('viagem', function ($q) use ($data) {
                                $q->whereIn('motivo_divergencia', $data['motivo_divergencia']);
                            })
                        );
                    }),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('viagem.veiculo', 'placa')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->options(Models\Integrado::query()
                        ->orderBy('nome')
                        ->pluck('nome', 'id'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                TernaryFilter::make('sem_complemento')
                    ->label('Sem Complemento')
                    ->placeholder('Todos')
                    ->trueLabel('Sim')
                    ->falseLabel('Não')
                    ->queries(
                        true: fn (Builder $query) => $query->doesntHave('viagem.complementos'),
                        false: fn (Builder $query) => $query->has('viagem.complementos'),
                        blank: fn (Builder $query) => $query,
                    ),
                Filter::make('data_competencia')
                    ->columns(6)
                    ->schema([
                        DatePicker::make('data_inicio')
                            ->label('Data Comp. Início')
                            ->columnSpan(2),
                        DatePicker::make('data_fim')
                            ->label('Data Comp. Fim')
                            ->columnSpan(2),
                    ])
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_inicio'],
                                fn(Builder $query, $date) =>
                                $query->whereHas('viagem', fn($q) => $q->whereDate('data_competencia', '>=', $date))
                            )
                            ->when(
                                $data['data_fim'],
                                fn(Builder $query, $date) =>
                                $query->whereHas('viagem', fn($q) => $q->whereDate('data_competencia', '<=', $date))
                            );
                    }),
                QueryBuilder::make()
                    ->constraints([
                            \Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('km_cobrar')
                                ->relationship('viagem','km_cobrar'),
                            \Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('km_perdido'),
                            \Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('integrado')
                                ->multiple()
                                ->emptyable()
                                ->selectable(
                                    \Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator::make()
                                        ->titleAttribute('nome')
                                        ->searchable()
                                        ->multiple()
                                )
                        ])
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->searchOnBlur()
            ->persistFiltersInSession()
            ->recordActions([
                DeleteAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
