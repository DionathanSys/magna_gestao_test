<?php

namespace App\Filament\Resources\CteEmailRequests\Pages;

use App\Filament\Resources\CteEmailRequests\CteEmailRequestResource;
use App\Services\Bugio\CteReturnEmailProcessingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCteEmailRequest extends ViewRecord
{
    protected static string $resource = CteEmailRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocessar_request')
                ->label('Reprocessar Anexos')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (CteReturnEmailProcessingService $service): void {
                    $service->reprocessRequest($this->record->id);

                    Notification::make()
                        ->success()
                        ->title('Reprocessamento disparado')
                        ->body("Request {$this->record->id} enviado para reprocessamento dos anexos.")
                        ->send();
                }),
        ];
    }
}
