<?php

namespace App\Filament\Tables;

use App\Models\Viagem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SelectTableViagem
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Viagem::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                
                $arguments = $table->getArguments();

                if (isset($arguments['veiculo_id'])) {
                    $query->where('veiculo_id', $arguments['veiculo_id']);
                }
                return $query;
            })
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numero_viagem')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->searchable(),
                TextColumn::make('km_rodado')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('data_competencia')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('condutor')
                    ->searchable(),
                TextColumn::make('unidade_negocio')
                    ->searchable(),
                TextColumn::make('cliente')
                    ->searchable(),
            ])
            ->poll(null)
            ->filters([
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
