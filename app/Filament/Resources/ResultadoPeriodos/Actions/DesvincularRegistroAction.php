<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DesvincularRegistroAction
{
    public static function make(): Action
    {
        return Action::make('desvincular_periodo')
            ->label('Desvincular Período')
            ->icon(Heroicon::XMark)
            ->color('warning')
            ->requiresConfirmation()
            ->action(function ($record) {
                $record->update(['resultado_periodo_id' => null]);
                notify::success(mensagem: 'Abastecimento desvinculado com sucesso!');
            });
    }
}
