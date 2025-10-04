<?php

namespace App\Filament\Resources\Agendamentos\Actions;

use App\{Models, Services};
use Filament\Actions\BulkAction;
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CancelarAgendamentoAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('cancelar_agendamento')
            ->label('Cancelar Agendamento')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->color('danger')
            ->action(function (Collection $records) {
                $records->each(function (Models\Agendamento $record) {
                    $service = new Services\Agendamento\AgendamentoService();
                    $service->cancelar($record);
                    if ($service->hasError()) {
                        notify::error(mensagem: 'Agendamento: ' . $record->id . '<br>' . $service->getMessage());
                        return;
                    }
                    notify::success(mensagem: 'Agendamento: ' . $record->id . '<br>' . $service->getMessage());
                    Log::info("Agendamento ID {$record->id} cancelado com sucesso.", [
                        'data' => $record->toArray(),
                    ]);
                });
            })
            ->deselectRecordsAfterCompletion();
    }
}
