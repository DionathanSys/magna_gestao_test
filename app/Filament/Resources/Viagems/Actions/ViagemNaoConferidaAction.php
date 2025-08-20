<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;

class ViagemNaoConferidaAction
{
    public static function make(): Action
    {
        return Action::make('nao-conferido')
            ->label('Ñ Conferido')
            ->iconButton()
            ->icon('heroicon-o-no-symbol')
            ->color('red')
            ->visible(fn(Models\Viagem $record) => $record->conferido)
            ->action(function (Models\Viagem $record) {
                $service = new Services\Viagem\ViagemService();
                $service->marcarViagemComoNãoConferida($record);
                if ($service->hasError()) {
                    notify::error('Erro ao marcar viagem como não conferida', $service->getMessage());
                    return;
                }
                notify::success();
            });
    }
}
