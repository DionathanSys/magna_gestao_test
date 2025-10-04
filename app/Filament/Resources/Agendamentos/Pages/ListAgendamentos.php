<?php

namespace App\Filament\Resources\Agendamentos\Pages;

use App\{Models, Services};
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Arr;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgendamentos extends ListRecords
{
    protected static string $resource = AgendamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Agendamento')
                ->icon('heroicon-o-plus')
                ->using(function (array $data, string $model): Models\Agendamento {
                    $service = new Services\Agendamento\AgendamentoService();
                    $agendamento = $service->create($data);

                    if ($service->hasError()) {
                        notify::error(mensagem: $service->getMessage());
                        $this->halt();
                    }

                    return $agendamento;
                }),
        ];
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        return Arr::only($data, ['veiculo_id', 'data_agendamento', 'data_limite', 'parceiro_id']);
    }
}
