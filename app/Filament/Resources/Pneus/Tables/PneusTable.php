<?php

namespace App\Filament\Resources\Pneus\Tables;

use App\Models;
use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use App\Filament\Resources\Veiculos\VeiculoResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PneusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->width('1%'),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->url(fn(Models\Pneu $record): string => VeiculoResource::getUrl('edit', ['record' => $record->veiculo->id]))
                    ->openUrlInNewTab(),
                TextColumn::make('numero_fogo')
                    ->label('Nº de Fogo')
                    ->width('1%')
                    ->numeric(null, '', '')
                    ->searchable(isIndividual: true),
                TextColumn::make('km_percorrido_ciclo')
                    ->label('Km Ciclo Atual')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_percorrido')
                    ->label('Km Total')
                    ->width('1%')
                    ->numeric(0, ',', '.'),
                TextColumn::make('marca')
                    ->width('1%')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('modelo')
                    ->width('1%')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('medida')
                    ->width('1%')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('desenhoPneu.medida')
                    ->label('Medida Sulco (mm)')
                    ->wrapHeader()
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('ciclo_vida')
                    ->label('Vida')
                    ->wrapHeader()
                    ->width('1%'),
                SelectColumn::make('status')
                    ->width('1%')
                    ->options(StatusPneuEnum::toSelectArray()),
                SelectColumn::make('local')
                    ->options(LocalPneuEnum::toSelectArray())
                    ->width('1%'),
                TextColumn::make('ultimoRecap.desenhoPneu.descricao')
                    ->label('Borracha Recap Atual')
                    ->wrapHeader()
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('data_aquisicao')
                    ->label('Dt. Aquisição')
                    ->date('d/m/Y')
                    ->sortable()
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
            ])
            ->filters([
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
