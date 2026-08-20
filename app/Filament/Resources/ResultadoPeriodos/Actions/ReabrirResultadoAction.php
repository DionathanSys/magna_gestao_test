<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Enum\StatusDiversosEnum;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ReabrirResultadoAction
{
    public static function make(): Action
    {
        return Action::make('reabrir_resultado')
            ->label('Reabrir resultado')
            ->icon(Heroicon::ArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Reabra apenas para corrigir vínculos ou custos. O período voltará a aceitar alterações.')
            ->visible(fn ($record): bool => $record->status === StatusDiversosEnum::ENCERRADO->value)
            ->action(function ($record): void {
                $record->update(['status' => StatusDiversosEnum::PENDENTE->value]);
                notify::success(mensagem: 'Resultado reaberto para ajustes.');
            });
    }
}
