<?php

namespace App\Filament\Resources\Checklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChecklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->sortable(),
                TextColumn::make('data_referencia')
                    ->label('Data Realização')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('periodo')
                    ->date('M/Y')
                    ->sortable(),
                TextColumn::make('quilometragem')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('itens_verificados_count')
                    ->label('Itens Verificados')
                    ->sortable(),
                TextColumn::make('pendencias_count')
                    ->label('Pendências')
                    ->sortable(),
                TextColumn::make('itens_corrigidos_count')
                    ->label('Itens Corrigidos')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->sortable(),
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
            ])
            ->groups([
                Group::make('veiculo.placa')
                    ->label('Veículo'),
                Group::make('periodo')
                    ->label('Período')
                    ->date()
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('created_at', $direction)),

            ])
            ->paginated([30])
            ->defaultPaginationPageOption(30)
            ->defaultGroup('periodo')
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
