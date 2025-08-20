<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;

class ViagemConferidaAction
{
    public static function make(): Action
    {
        return Action::make('conferido')
            ->label('Conferido')
            ->iconButton()
            ->icon('heroicon-o-check-circle')
            ->visible(fn(Models\Viagem $record) => ! $record->conferido)
            ->action(function (Models\Viagem $record) {
                $service = new Services\Viagem\ViagemService();
                $service->marcarViagemComoConferida($record);
                if ($service->hasError()) {
                    notify::error('Erro ao marcar viagem como conferida', $service->getMessage());
                    return;
                }
                notify::success();
            });
    }
}
