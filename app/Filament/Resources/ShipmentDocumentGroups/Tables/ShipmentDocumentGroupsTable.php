<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentDocumentGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['saleDocument', 'remittanceDocument', 'integrado', 'viagem']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('sale_number')->label('Nota Venda')->placeholder('-'),
                TextColumn::make('remittance_number')->label('Nota Remessa')->placeholder('-'),
                TextColumn::make('integrado.nome')->label('Integrado')->placeholder('-')->wrap(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap(),
                TextColumn::make('viagem.id')->label('Viagem')->placeholder('-'),
                TextColumn::make('matched_at')->label('Pareado em')->dateTime('d/m/Y H:i')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'matched' => 'Matched',
                        'pending_data' => 'Pending data',
                        'trip_created' => 'Trip created',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
            ]);
    }
}
