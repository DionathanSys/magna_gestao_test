<?php

namespace App\Filament\Actions;

use App\Services\ResultadoPeriodo\ResultadoPeriodoVinculoService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class VincularResultadoPeriodoAction
{
    public static function make(): Action
    {
        return Action::make('vincular_resultado_periodo')
            ->label('Vincular / mover resultado')
            ->icon('heroicon-o-link')
            ->iconButton()
            ->tooltip('Vincular ou mover o registro para outro resultado período')
            ->color('primary')
            ->modalHeading('Vincular ao resultado período')
            ->modalDescription('O destino e o vínculo atual precisam estar pendentes. Registros de períodos encerrados são preservados.')
            ->schema(ResultadoPeriodoVinculoForm::schema())
            ->action(function (Model $record, array $data, ResultadoPeriodoVinculoService $service): void {
                try {
                    $alterado = $service->vincular(
                        $record,
                        $data['estrategia'],
                        $data['data_referencia'] ?? null,
                        $data['resultado_periodo_id'] ?? null,
                    );

                    Notification::make()
                        ->title($alterado ? 'Registro vinculado ao resultado período.' : 'O registro já pertence ao resultado selecionado.')
                        ->success()
                        ->send();
                } catch (\RuntimeException $exception) {
                    Notification::make()
                        ->title('Vínculo não realizado')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
