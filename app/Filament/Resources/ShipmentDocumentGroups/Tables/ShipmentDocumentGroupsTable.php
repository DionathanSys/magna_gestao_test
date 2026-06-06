<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Tables;

use App\Models\ShipmentDocumentGroup;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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
                Action::make('reprocessar_viagem')
                    ->label('Reprocessar Viagem')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->action(function (ShipmentDocumentGroup $record, ShipmentTripService $shipmentTripService): void {
                        $shipmentTripService->createFromGroup($record->id);

                        Notification::make()
                            ->success()
                            ->title('Grupo reprocessado')
                            ->body("Grupo {$record->id} reavaliado para criacao da viagem.")
                            ->send();
                    }),
                ViewAction::make()->iconButton(),
            ]);
    }
}
