<?php

namespace App\Filament\Resources\Viagems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ViagemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('veiculo_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numero_viagem')
                    ->searchable(),
                TextColumn::make('numero_custo_frete')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->searchable(),
                TextColumn::make('tipo_viagem')
                    ->searchable(),
                TextColumn::make('valor_frete')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_cte')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_nfs')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_icms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_rodado')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_pago')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_divergencia')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_cadastro')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_rota_corrigido')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_pago_excedente')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_rodado_excedente')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_cobrar')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('motivo_divergencia')
                    ->searchable(),
                TextColumn::make('peso')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('entregas')
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
