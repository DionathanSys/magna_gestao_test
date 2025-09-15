<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus\Tables;

use App\Filament\Resources\Pneus\PneuResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class HistoricoMovimentoPneusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pneu.numero_fogo')
                    ->label('Nº de Fogo')
                    ->width('1%')
                    ->sortable()
                    ->url(fn($record) => PneuResource::getUrl('view', ['record' => $record->pneu_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('data_inicial')
                    ->label('Dt. Inicial')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_final')
                    ->label('Dt. Final')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('km_inicial')
                    ->label('KM Inicial')
                    ->width('1%')
                    ->numeric(null, '', '.')
                    ->searchable(),
                TextColumn::make('km_final')
                    ->label('KM Final')
                    ->width('1%')
                    ->numeric(null, '', '.')
                    ->searchable(),
                TextColumn::make('kmPercorrido')
                    ->label('KM Percorrido')
                    ->width('1%')
                    ->numeric(null, '', '.')
                    ->summarize(
                        Summarizer::make()
                            ->label('Total KM Percorrido')
                            ->using(function ($query) {
                                // Busca todos os registros filtrados
                                $total = $query->get()->sum(fn($item) => $item->km_final - $item->km_inicial);
                                return number_format($total, 2, ',', '.');
                            })
                    ),
                TextColumn::make('ciclo_vida')
                    ->label('Vida Pneu')
                    ->width('1%'),
                TextColumn::make('eixo')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('posicao')
                    ->label('Posição')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('sulco_movimento')
                    ->label('Sulco')
                    ->width('1%')
                    ->wrapHeader()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('motivo')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('observacao')
                    ->label('Observação')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('pneu_id')
                    ->label('Pneu')
                    ->relationship('pneu', 'numero_fogo')
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable(),
                DateRangeFilter::make('data_inicial')
                    ->label('Dt. Aplicação')
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('data_final')
                    ->label('Dt. Remoção')
                    ->alwaysShowCalendar()
            ])
            ->defaultSort('id', 'desc')
            ->defaultGroup('pneu.numero_fogo')
            ->groups([
                Group::make('pneu.numero_fogo')
                    ->label('Nº de Fogo')
                    ->collapsible(),
                Group::make('pneu.ciclo_vida')
                    ->label('Ciclo de Vida')
                    ->collapsible(),
                Group::make('veiculo.placa')
                    ->label('Placa')
                    ->collapsible(),
                Group::make('data_inicial')
                    ->label('Dt. Aplicação')
                    ->collapsible(),
                Group::make('data_final')
                    ->label('Dt. Remoção')
                    ->collapsible(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                ViewAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->disabled(fn(): bool => !Auth::user()->is_admin)
                    ->iconButton(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
