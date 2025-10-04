<?php

namespace App\Filament\Resources\Agendamentos\Actions;

use App\{Models, Services, Enum};
use Filament\Actions\BulkAction;
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EncerrarAgendamentoAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('encerrar_agendamento')
            ->label('Encerrar Agendamento')
            ->icon('heroicon-o-clipboard-document-check')
            ->requiresConfirmation()
            ->action(function (Collection $records) {
                $records->each(function (Models\Agendamento $record) {
                    $service = new Services\Agendamento\AgendamentoService();
                    $service->encerrar($record);
                    if ($service->hasError()) {
                        notify::error(mensagem: 'Agendamento: ' . $record->id . '<br>' . $service->getMessage());
                        return;
                    }
                    notify::success(mensagem: 'Agendamento: ' . $record->id . '<br>' . $service->getMessage());
                    Log::info("Agendamento ID {$record->id} encerrado com sucesso.");
                });
            })
            ->deselectRecordsAfterCompletion();
    }
}
