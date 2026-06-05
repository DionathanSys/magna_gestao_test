<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Pages;

use App\Filament\Resources\ReceivedFiscalDocuments\ReceivedFiscalDocumentResource;
use App\Jobs\MailInbound\CreateTripFromShipmentDocumentsJob;
use App\Services\MailInbound\ShipmentDocumentMatcher;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivedFiscalDocument extends ViewRecord
{
    protected static string $resource = ReceivedFiscalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocessar_documento')
                ->label('Reprocessar Documento')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (ShipmentDocumentMatcher $matcher): void {
                    $group = $matcher->match($this->record->fresh());

                    if ($group) {
                        CreateTripFromShipmentDocumentsJob::dispatch($group->id)
                            ->onQueue(config('mail-inbound.queue.trip'));
                    }

                    Notification::make()
                        ->success()
                        ->title('Documento reprocessado')
                        ->body("Documento fiscal {$this->record->id} reavaliado para pareamento.")
                        ->send();
                }),
        ];
    }
}
