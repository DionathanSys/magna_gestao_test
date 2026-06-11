<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Tables;

use App\Models\ShipmentDocumentGroup;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentDocumentGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['saleDocument', 'remittanceDocument', 'integrado', 'viagem']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                TextColumn::make('sale_number')->label('Nota Venda')->placeholder('-')->toggleable(),
                TextColumn::make('remittance_number')->label('Nota Remessa')->placeholder('-')->toggleable(),
                TextColumn::make('integrado.nome')->label('Integrado')->placeholder('-')->wrap()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->toggleable(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap()->toggleable(),
                TextColumn::make('viagem.id')->label('Viagem')->placeholder('-')->toggleable(),
                TextColumn::make('matched_at')->label('Pareado em')->dateTime('d/m/Y H:i')->placeholder('-')->toggleable(),
            ])
            ->filters([
                Filter::make('id')
                    ->label('ID')
                    ->schema([
                        TextInput::make('id')->label('ID')->numeric(),
                    ])
                    ->indicateUsing(fn(array $data): ?string => filled($data['id'] ?? null) ? "ID: {$data['id']}" : null)
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        filled($data['id'] ?? null),
                        fn(Builder $query): Builder => $query->whereKey($data['id']),
                    )),
                Filter::make('sale_number')
                    ->label('Nota Venda')
                    ->schema([
                        TextInput::make('sale_number')->label('Nota Venda'),
                    ])
                    ->indicateUsing(fn(array $data): ?string => filled($data['sale_number'] ?? null) ? "Nota Venda: {$data['sale_number']}" : null)
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        filled($data['sale_number'] ?? null),
                        fn(Builder $query): Builder => $query->where('sale_number', 'like', "%{$data['sale_number']}%"),
                    )),
                Filter::make('remittance_number')
                    ->label('Nota Remessa')
                    ->schema([
                        TextInput::make('remittance_number')->label('Nota Remessa'),
                    ])
                    ->indicateUsing(fn(array $data): ?string => filled($data['remittance_number'] ?? null) ? "Nota Remessa: {$data['remittance_number']}" : null)
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        filled($data['remittance_number'] ?? null),
                        fn(Builder $query): Builder => $query->where('remittance_number', 'like', "%{$data['remittance_number']}%"),
                    )),
                SelectFilter::make('status')
                    ->options([
                        'matched' => 'Matched',
                        'pending_data' => 'Pending data',
                        'trip_created' => 'Trip created',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('integrado', 'nome')
                    ->searchable()
                    ->multiple(),
                TernaryFilter::make('possui_viagem')
                    ->label('Viagem vinculada?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Com viagem')
                    ->falseLabel('Sem viagem')
                    ->queries(
                        true: fn(Builder $query): Builder => $query->whereNotNull('viagem_id'),
                        false: fn(Builder $query): Builder => $query->whereNull('viagem_id'),
                        blank: fn(Builder $query): Builder => $query,
                    ),
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
