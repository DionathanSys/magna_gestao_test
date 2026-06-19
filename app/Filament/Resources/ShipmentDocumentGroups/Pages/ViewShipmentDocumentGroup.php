<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Pages;

use App\Filament\Resources\ShipmentDocumentGroups\ShipmentDocumentGroupResource;
use App\Models\Veiculo;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewShipmentDocumentGroup extends ViewRecord
{
    protected static string $resource = ShipmentDocumentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocessar_viagem')
                ->label('Reprocessar Viagem')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->schema([
                    Select::make('veiculo_id')
                        ->label('Placa / Veiculo')
                        ->helperText('Use este campo quando a nota chegou sem placa no XML/email.')
                        ->options(fn (): array => Veiculo::query()
                            ->where('is_active', true)
                            ->orderBy('placa')
                            ->pluck('placa', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => $this->record->payload['veiculo_id'] ?? null),
                ])
                ->action(function (array $data, ShipmentTripService $shipmentTripService): void {
                    $payload = [
                        ...($this->record->payload ?? []),
                        'veiculo_id' => $data['veiculo_id'] ?? null,
                        'placa_manual' => filled($data['veiculo_id'] ?? null)
                            ? Veiculo::query()->whereKey($data['veiculo_id'])->value('placa')
                            : null,
                    ];

                    $this->record->update(['payload' => $payload]);

                    $shipmentTripService->createFromGroup($this->record->id);

                    Notification::make()
                        ->success()
                        ->title('Grupo reenviado')
                        ->body("Grupo {$this->record->id} reenviado para tentativa de criacao da viagem.")
                        ->send();
                }),
        ];
    }
}
