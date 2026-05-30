<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models\Viagem;
use App\Services\Carga\CargaService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GerarComplementarCargaAction
{
    public static function make(): Action
    {
        return Action::make('gerar-complementar-carga')
            ->label('Gerar/Complementar Carga')
            ->icon('heroicon-o-squares-plus')
            ->action(function (Viagem $record) {
                try {
                    $carga = (new CargaService())->gerarOuComplementar(null, $record);

                    if (! $carga) {
                        Notification::make()
                            ->warning()
                            ->title('Nenhuma carga criada')
                            ->body('A viagem possui mais destinos previstos do que cargas cadastradas. Nenhuma nova carga foi criada automaticamente.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Carga processada com sucesso')
                        ->body('A carga foi criada ou complementada sem duplicação.')
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('Erro ao processar carga')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
