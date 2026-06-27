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
            ->visible(fn (Pneu $record) => in_array($record->status->value, ['DISPONIVEL', 'INDISPONIVEL'], true)
                && in_array($record->local?->value, ['ESTOQUE CCO', 'AGUARDANDO RECAPAGEM'], true))
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
