<?php

namespace App\Filament\Actions;

use App\Services\ResultadoPeriodo\ResultadoPeriodoVinculoService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class VincularResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular_resultado_periodo')
            ->label('Vincular / mover resultado')
            ->icon('heroicon-o-link')
            ->color('primary')
            ->modalHeading('Vincular registros ao resultado período')
            ->modalDescription('Cada registro será validado pelo veículo e pelo status dos períodos de origem e destino.')
            ->schema(ResultadoPeriodoVinculoForm::schema())
            ->action(function (Collection $records, array $data, ResultadoPeriodoVinculoService $service): void {
                $vinculados = 0;
                $inalterados = 0;
                $recusados = [];

                $records->each(function (Model $record) use ($data, $service, &$vinculados, &$inalterados, &$recusados): void {
                    try {
                        $service->vincular(
                            $record,
                            $data['estrategia'],
                            $data['data_referencia'] ?? null,
                            $data['resultado_periodo_id'] ?? null,
                        ) ? $vinculados++ : $inalterados++;
                    } catch (\RuntimeException $exception) {
                        $recusados[] = "#{$record->getKey()}: {$exception->getMessage()}";
                    }
                });

                Notification::make()
                    ->title("{$vinculados} vinculado(s), {$inalterados} sem alteração e ".count($recusados).' recusado(s).')
                    ->body($recusados === [] ? null : implode('\n', array_slice($recusados, 0, 5)))
                    ->color($recusados === [] ? 'success' : 'warning')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
