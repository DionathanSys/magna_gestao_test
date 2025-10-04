<?php

namespace App\Filament\Resources\Agendamentos\Actions;

use App\{Models, Services, Enum};
use Filament\Actions\BulkAction;
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VincularOrdemServicoAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular_ordem_servico')
            ->label('Vincular c/ OS')
            ->icon('heroicon-o-clipboard-document-list')
            ->requiresConfirmation()
            ->action(function (Collection $records) {
                $records->each(function (Models\Agendamento $record) {
                    if ($record->status == Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $record->ordem_servico_id === null) {
                        $service = new Services\Agendamento\AgendamentoService();
                        $service->vincularEmOrdemServico($record);
                        if ($service->hasError()) {
                            notify::error(mensagem: 'Agendamento: ' . $record->id . '<br>' . $service->getMessage());
                            return;
                        }
                        notify::success(mensagem: 'Agendamento: ' . $record->id . '<br>' . $service->getMessage());
                        Log::info("Agendamento ID {$record->id} vinculado à Ordem de Serviço ID {$record->ordem_servico_id} com sucesso.");
                    }
                });
            })
            ->deselectRecordsAfterCompletion();
    }
}
