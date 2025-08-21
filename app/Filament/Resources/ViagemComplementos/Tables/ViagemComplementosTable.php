<?php

namespace App\Filament\Resources\ViagemComplementos\Tables;

use App\Models;
use Carbon\Carbon;
use App\Filament\Resources\ViagemComplementos;
use Filament\Actions\{BulkActionGroup, CreateAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\{DatePicker, TextInput};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViagemComplementosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->numeric(0,'','')
                    ->sortable(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('numero_viagem')
                    ->label('Nº Viagem')
                    ->numeric(0,'','')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('documento_transporte')
                    ->numeric(0,'','')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('integrado.codigo')
                    ->numeric(0,'','')
                    ->sortable(),
                TextColumn::make('integrado.nome')
                    ->label('Integrado')
                    ->sortable(),
                TextColumn::make('km_rodado')
                    ->numeric(2, ',','.')
                    ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->numeric(2, ',','.')
                    ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                    ->sortable(),
                TextColumn::make('km_divergencia')
                    ->numeric(2, ',','.')
                    ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                    ->sortable(),
                TextColumn::make('km_cobrar')
                    ->numeric(2, ',','.')
                    ->summarize(Sum::make()->numeric(decimalPlaces: 2, locale: 'pt-BR'))
                    ->sortable(),
                TextColumn::make('motivo_divergencia')
                    ->label('Motivo Divergência')
                    ->searchable(),
                TextColumn::make('data_competencia')
                    ->label('Data Competência')
                    ->date('d/m/Y')
                    ->searchable(),
                IconColumn::make('conferido')
                    ->boolean(),
                TextColumn::make('status')
                    ->searchable(),
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
                    Group::make('data_competencia')
                        ->label('Data Competência')
                        ->titlePrefixedWithLabel(false)
                        ->getTitleFromRecordUsing(fn(Models\ViagemComplemento $record): string => Carbon::parse($record->data_competencia)->format('d/m/Y'))
                        ->collapsible(),
                    Group::make('integrado.nome')
                        ->label('Integrado')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                    Group::make('veiculo.placa')
                        ->label('Veículo')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                    Group::make('numero_viagem')
                        ->label('Nº Viagem')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                ]
            )
            ->selectCurrentPageOnly()
            ->defaultGroup('numero_viagem')
            ->deferFilters()
            ->searchOnBlur()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->filters([
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('integrado', 'nome')
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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                ViagemComplementos\Actions\ComplementoConferidoAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
