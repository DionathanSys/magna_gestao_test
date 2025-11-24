<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ViagemBugiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('destinos')
                    ->label('Integrados')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('data_competencia')
                    ->label('Data Viagem')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('km_pago')
                    ->label('Km Pago')
                    ->numeric(0, ',', '.')
                    ->sortable()
                    ->summarize(Sum::make()),
                TextColumn::make('frete')
                    ->label('Frete')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('BRL')),
                TextColumn::make('condutor')
                    ->label('Motorista')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => Auth::user()->is_admin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->is_admin),
                ]),
            ]);
    }
}
