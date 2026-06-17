<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Tables;

use App\Filament\Actions\ExportPdfBulkAction;
use App\Models\ShipmentDocumentGroup;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['saleDocument', 'remittanceDocument', 'integrado', 'viagem']))
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
                    ->indicateUsing(fn (array $data): ?string => filled($data['id'] ?? null) ? "ID: {$data['id']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['id'] ?? null),
                        fn (Builder $query): Builder => $query->whereKey($data['id']),
                    )),
                Filter::make('sale_number')
                    ->label('Nota Venda')
                    ->schema([
                        TextInput::make('sale_number')->label('Nota Venda'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['sale_number'] ?? null) ? "Nota Venda: {$data['sale_number']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['sale_number'] ?? null),
                        fn (Builder $query): Builder => $query->where('sale_number', 'like', "%{$data['sale_number']}%"),
                    )),
                Filter::make('remittance_number')
                    ->label('Nota Remessa')
                    ->schema([
                        TextInput::make('remittance_number')->label('Nota Remessa'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['remittance_number'] ?? null) ? "Nota Remessa: {$data['remittance_number']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['remittance_number'] ?? null),
                        fn (Builder $query): Builder => $query->where('remittance_number', 'like', "%{$data['remittance_number']}%"),
                    )),
                SelectFilter::make('status')
                    ->label('Status')
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
                        true: fn (Builder $query): Builder => $query->whereNotNull('viagem_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('viagem_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('created_at')
                    ->label('Criado em')
                    ->schema([
                        DatePicker::make('created_from')->label('De'),
                        DatePicker::make('created_until')->label('Até'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['created_from'] ?? null)) {
                            $indicators[] = 'Criado de: ' . $data['created_from'];
                        }

                        if (filled($data['created_until'] ?? null)) {
                            $indicators[] = 'Criado até: ' . $data['created_until'];
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date))),
                Filter::make('matched_at')
                    ->label('Pareado em')
                    ->schema([
                        DatePicker::make('matched_from')->label('De'),
                        DatePicker::make('matched_until')->label('Até'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['matched_from'] ?? null)) {
                            $indicators[] = 'Pareado de: ' . $data['matched_from'];
                        }

                        if (filled($data['matched_until'] ?? null)) {
                            $indicators[] = 'Pareado até: ' . $data['matched_until'];
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['matched_from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('matched_at', '>=', $date))
                        ->when($data['matched_until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('matched_at', '<=', $date))),
            ])
            ->recordActions([
                Action::make('reprocessar_viagem')
                    ->label('Reprocessar Viagem')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->action(function (ShipmentDocumentGroup $record, ShipmentTripService $shipmentTripService): void {
                        $shipmentTripService->createFromGroup($record->id);

                        $group = $record->fresh()->load('viagem');

                        if ($group->viagem) {
                            Notification::make()
                                ->success()
                                ->title('Viagem criada')
                                ->body("Viagem {$group->viagem->numero_viagem} criada a partir do grupo {$record->id}.")
                                ->send();
                        } elseif ($group->status === 'pending_data') {
                            Notification::make()
                                ->warning()
                                ->title('Dados insuficientes')
                                ->body("Grupo {$record->id} pendente de dados (integrado, unidade ou veiculo).")
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Grupo reprocessado')
                                ->body("Grupo {$record->id} reavaliado.")
                                ->send();
                        }
                    }),
                ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportPdfBulkAction::make(
                        'exportar_pdf',
                        'Grupos de Notas',
                        [
                            ['key' => 'id', 'label' => 'ID', 'align' => 'center', 'width' => '5%'],
                            ['key' => 'nota_venda', 'label' => 'Nota Venda', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'nota_remessa', 'label' => 'Nota Remessa', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'integrado', 'label' => 'Integrado', 'width' => '18%'],
                            ['key' => 'status', 'label' => 'Status', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'pendencia', 'label' => 'O que falta', 'width' => '22%'],
                            ['key' => 'viagem', 'label' => 'Viagem', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'pareado_em', 'label' => 'Pareado em', 'align' => 'center', 'width' => '12%'],
                        ],
                        fn ($records) => $records->load(['integrado', 'viagem'])
                            ->map(fn ($r) => [
                                'id' => $r->id,
                                'nota_venda' => e($r->sale_number ?? '-'),
                                'nota_remessa' => e($r->remittance_number ?? '-'),
                                'integrado' => e($r->integrado?->nome ?? '-'),
                                'status' => e($r->status ?? '-'),
                                'pendencia' => e($r->pending_summary ?? '-'),
                                'viagem' => $r->viagem_id ? (string) $r->viagem_id : '-',
                                'pareado_em' => $r->matched_at?->format('d/m/Y H:i') ?? '-',
                            ])->toArray(),
                    ),
                ]),
            ]);
    }
}
