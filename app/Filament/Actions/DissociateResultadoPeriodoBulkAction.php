<?php

namespace App\Filament\Actions;

use App\Services\ResultadoPeriodo\ResultadoPeriodoVinculoService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DissociateResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('dissociate_resultado_periodo')
            ->label('Desvincular do resultado')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Desvincular registros do resultado')
            ->modalDescription('Somente registros de períodos pendentes serão desvinculados. Períodos encerrados serão preservados.')
            ->modalSubmitActionLabel('Sim, desvincular')
            ->action(function (Collection $records, ResultadoPeriodoVinculoService $service): void {
                $desvinculados = 0;
                $recusados = [];

                $records->each(function (Model $record) use ($service, &$desvinculados, &$recusados): void {
                    try {
                        $service->desvincular($record) ? $desvinculados++ : null;
                    } catch (\RuntimeException $exception) {
                        $recusados[] = "#{$record->getKey()}: {$exception->getMessage()}";
                    }
                });

                Notification::make()
                    ->title("{$desvinculados} registro(s) desvinculado(s) e ".count($recusados).' recusado(s).')
                    ->body($recusados === [] ? null : implode('\n', array_slice($recusados, 0, 5)))
                    ->color($recusados === [] ? 'success' : 'warning')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
