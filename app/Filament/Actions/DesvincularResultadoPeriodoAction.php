<?php

namespace App\Filament\Actions;

use App\Services\ResultadoPeriodo\ResultadoPeriodoVinculoService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DesvincularResultadoPeriodoAction
{
    public static function make(): Action
    {
        return Action::make('desvincular_resultado_periodo')
            ->label('Desvincular do resultado')
            ->icon('heroicon-o-x-mark')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('O registro só será removido se o resultado período atual estiver pendente.')
            ->visible(fn (Model $record): bool => filled($record->resultado_periodo_id))
            ->action(function (Model $record, ResultadoPeriodoVinculoService $service): void {
                try {
                    $service->desvincular($record);

                    Notification::make()
                        ->title('Registro desvinculado do resultado período.')
                        ->success()
                        ->send();
                } catch (\RuntimeException $exception) {
                    Notification::make()
                        ->title('Desvínculo não realizado')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
