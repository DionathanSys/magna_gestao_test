<?php

namespace App\Filament\Resources\ViagemComplementos\Actions;

use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Collection;

class ComplementoConferidoAction
{
    public static function make(): Action
    {
        return Action::make('conferido')
            ->label('Conferir')
            ->icon('heroicon-o-check-circle')
            ->action(function (Collection $records) {
                $records->each(function (Models\ViagemComplemento $record) {
                    $record->conferido = true;
                    $record->save();
                });
            })
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation()
            ->successNotificationTitle('Complementos conferidos')
            ->failureNotificationTitle(function (int $successCount, int $totalCount): string {
                if ($successCount) {
                    return "{$successCount} of {$totalCount} users deleted";
                }

                return 'Failed to delete any users';
            });
    }
}
