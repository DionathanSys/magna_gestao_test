<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Enum\StatusDiversosEnum;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class EncerrarResultadoAction
{
    public static function make(): Action
    {
        return Action::make('encerrar_resultado')
            ->label('Encerrar Resultado')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->action(function ($record) {
                $record->update(['status' => StatusDiversosEnum::ENCERRADO->value]);
                notify::success();
            });
    }
}
