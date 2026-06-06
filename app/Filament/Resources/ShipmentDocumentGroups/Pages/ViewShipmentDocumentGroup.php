<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Pages;

use App\Filament\Resources\ShipmentDocumentGroups\ShipmentDocumentGroupResource;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
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
                ->action(function (ShipmentTripService $shipmentTripService): void {
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
