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
            ->modalDescription('Após encerrar, vínculos e custos do período ficam protegidos contra alterações.')
            ->visible(fn ($record): bool => $record->status === StatusDiversosEnum::PENDENTE->value)
            ->action(function ($record) {
                $record->update(['status' => StatusDiversosEnum::ENCERRADO->value]);
                notify::success();
            });
    }
}
