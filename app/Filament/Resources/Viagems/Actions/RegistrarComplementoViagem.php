<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;

class RegistrarComplementoViagem
{
    public static function make(): BulkAction
    {
        return BulkAction::make('registrar-complemento')
            ->label('Registrar Complemento')
            ->icon('heroicon-o-banknotes')
            ->action(function (Collection $records) {
                $records->each(function (Models\Viagem $record) {
                    if ($record->km_cobrar > 0) {
                        (new Services\Viagem\ViagemComplementoService)->create($record);
                    }
                });
            })
            ->after(fn() => notify::success('Viagem registrada para cobrança!'))
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation();
    }
}
