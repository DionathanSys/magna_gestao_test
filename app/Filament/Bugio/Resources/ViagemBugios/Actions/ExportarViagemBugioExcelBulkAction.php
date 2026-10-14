<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Services\Relatorios\ExportarViagensBugioXlsxService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ExportarViagemBugioExcelBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-excel')
            ->label('Exportar Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records) => 'Você está prestes a exportar '.$records->count().' viagem(ns) Bugio para Excel.'
            )
            ->action(function (Collection $records) {
                if ($records->count() > 5000) {
                    Notification::make()
                        ->danger()
                        ->title('Muitos registros')
                        ->body('Por favor, selecione no máximo 5000 registros para exportar.')
                        ->send();

                    return;
                }

                return app(ExportarViagensBugioXlsxService::class)->export($records);
            })
            ->deselectRecordsAfterCompletion();
    }
}
