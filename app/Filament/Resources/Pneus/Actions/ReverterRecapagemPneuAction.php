<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Models\Pneu;
use App\Services\NotificacaoService as notify;
use App\Services\Pneus\PneuService;
use Filament\Actions\Action;

class ReverterRecapagemPneuAction
{
    public static function make(): Action
    {
        return Action::make('reverter-recapagem')
            ->label('Reverter Recapagem')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reverter recapagem')
            ->modalDescription('Remove a última recapagem, volta a vida do pneu em 1 e reabre o ciclo anterior.')
            ->visible(fn (Pneu $record): bool => $record->ciclo_vida > 0 && $record->recapagens()->exists())
            ->action(function (Action $action, Pneu $record): void {
                $service = new PneuService;
                $service->reverterRecapagem($record);

                if ($service->hasError()) {
                    notify::error(titulo: 'Falha ao reverter recapagem', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Recapagem revertida com sucesso.');
            });
    }
}
