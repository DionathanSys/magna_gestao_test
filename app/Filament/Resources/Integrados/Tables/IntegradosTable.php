<?php

namespace App\Filament\Resources\Integrados\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enum;
use Filament\Tables\Filters\SelectFilter;

class IntegradosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('nome')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('km_rota')
                    ->label('KM Rota')
                    ->width('1%')
                    ->numeric(2, ',', '.'),
                TextColumn::make('municipio')
                    ->label('Município')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('latitude'),
                TextColumn::make('longitude'),
                TextColumn::make('cliente'),
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
                TextColumn::make('deleted_at')
                    ->label('Deletado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->searchDebounce('750ms')
            ->filters([
                SelectFilter::make('cliente')
                    ->label('Cliente')
                    ->native(false)
                    ->options(Enum\ClienteEnum::toSelectArray())
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
