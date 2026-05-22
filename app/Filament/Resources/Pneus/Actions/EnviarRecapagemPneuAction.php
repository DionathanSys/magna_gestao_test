<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Models\Pneu;
use App\Services\NotificacaoService as notify;
use App\Services\Pneus\PneuService;
use Filament\Actions\Action;

class EnviarRecapagemPneuAction
{
    public static function make(): Action
    {
        return Action::make('enviar-recapagem')
            ->label('Enviar para Recap')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Pneu $record) => $record->status->value === 'INDISPONIVEL' && $record->local?->value === 'AGUARDANDO RECAPAGEM')
            ->action(function (Action $action, Pneu $record): void {
                $service = new PneuService;
                $service->enviarParaRecapagem($record);

                if ($service->hasError()) {
                    notify::error(titulo: 'Falha ao enviar para recapagem', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Pneu enviado para recapagem.');
            });
    }
}
