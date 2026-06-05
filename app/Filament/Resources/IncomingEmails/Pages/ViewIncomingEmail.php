<?php

namespace App\Filament\Resources\IncomingEmails\Pages;

use App\Filament\Resources\IncomingEmails\IncomingEmailResource;
use App\Services\MailInbound\InboundMessageIngestionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewIncomingEmail extends ViewRecord
{
    protected static string $resource = IncomingEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocessar_email')
                ->label('Reprocessar Email')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (InboundMessageIngestionService $service): void {
                    $service->reprocessStoredEmail($this->record->id);

                    Notification::make()
                        ->success()
                        ->title('Reprocessamento enfileirado')
                        ->body("Email {$this->record->id} enviado para reprocessamento fiscal.")
                        ->send();
                }),
        ];
    }
}
