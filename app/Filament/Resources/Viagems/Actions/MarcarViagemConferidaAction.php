<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MarcarViagemConferidaAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('conferido')
            ->label('Conferir')
            ->icon('heroicon-o-check-circle')
            ->action(function (Collection $records) {
                $service = new Services\Viagem\ViagemService();
                $errors = 0;
                $success = 0;
                $records->each(function (Models\Viagem $record) use ($service, &$errors, &$success) {

                    $service->marcarViagemComoConferida($record);

                    if($service->hasError()){
                        Log::error('Não foi possivel atualizar o status para conferida', [
                            'error' => $service->getData(),
                        ]);
                        $errors++;
                        return;
                    }

                    $success++;
                    return;
                });

                if($errors > 0){
                    notify::error("{$errors} viagem(ns) não foi(ram) marcada(s) como conferida(s). Favor tentar novamente.");
                }
                if($success > 0){
                    notify::success("{$success} viagem(ns) marcada(s) como conferida(s) com sucesso!");
                }
            })
            ->deselectRecordsAfterCompletion();
    }
}
