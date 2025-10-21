<?php

namespace App\Filament\Resources\Abastecimentos\Tables;

use App\Filament\Resources\Abastecimentos;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AbastecimentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_abastecimento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->searchable(),
                TextColumn::make('quilometragem')
                    ->numeric(0, ',', '.')
                    ->searchable(),
                TextColumn::make('posto_combustivel')
                    ->label('Posto de Combustível')
                    ->searchable(),
                TextColumn::make('tipo_combustivel')
                    ->label('Tipo de Combustível'),
                TextColumn::make('quantidade')
                    ->numeric(0, ',', '.')
                    ->sortable(),
                TextColumn::make('preco_por_litro')
                    ->label('Preço por Litro')
                    ->money('BRL', true)
                    ->sortable(),
                TextColumn::make('preco_total')
                    ->label('Preço Total')
                    ->money('BRL', true)
                    ->sortable(),
                TextColumn::make('data_abastecimento')
                    ->label('Dt. Abastecimento')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                IconColumn::make('considerar_fechamento')
                    ->label('Considerar Fechamento')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('considerar_calculo_medio')
                    ->label('Considerar Cálculo Médio')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Abastecimentos\Actions\ImportAbastecimentoAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
