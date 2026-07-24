<?php

namespace App\Filament\Actions;

use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class DissociateResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('dissociate_resultado_periodo')
            ->label('Dissociar de Resultado Período')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Desvincular Registros')
            ->modalDescription('Tem certeza que deseja desvincular os registros selecionados do resultado do período? Os registros não serão deletados.')
            ->modalSubmitActionLabel('Sim, desvincular')
            ->action(function (Collection $records) {
                $count = 0;

                $records->each(function ($record) use (&$count) {
                    if ($record->resultado_periodo_id) {
                        $record->resultado_periodo_id = null;
                        $record->save();
                        $count++;
                    }
                });

                notify::success("{$count} registro(s) foram desvinculados do resultado do período.");

                return true;
            })
            ->deselectRecordsAfterCompletion();
    }
}
