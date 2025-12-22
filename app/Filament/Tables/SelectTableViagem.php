<?php

namespace App\Filament\Tables;

use App\Models\Viagem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SelectTableViagem
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Viagem::query())
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
                TextColumn::make('km_cadastro')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_cobrar')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('motivo_divergencia')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_competencia')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('conferido')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checked_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_dispersao')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dispersao_percentual')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('condutor')
                    ->searchable(),
                IconColumn::make('considerar_relatorio')
                    ->boolean(),
                TextColumn::make('unidade_negocio')
                    ->searchable(),
                TextColumn::make('cliente')
                    ->searchable(),
                TextColumn::make('resultadoPeriodo.id')
                    ->searchable(),
            ])
            ->filters([
                //
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
