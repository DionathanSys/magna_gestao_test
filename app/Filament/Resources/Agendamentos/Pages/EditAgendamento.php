<?php

namespace App\Filament\Resources\Agendamentos\Pages;

use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Services\Agendamento\AgendamentoHistoricoService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditAgendamento extends EditRecord
{
    protected static string $resource = AgendamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $antes = [
            'veiculo_id' => $record->veiculo_id,
            'data_agendamento' => optional($record->data_agendamento)->format('Y-m-d'),
            'data_limite' => optional($record->data_limite)->format('Y-m-d'),
            'servico_id' => $record->servico_id,
            'posicao' => $record->posicao,
            'plano_preventivo_id' => $record->plano_preventivo_id,
            'observacao' => $record->observacao,
            'parceiro_id' => $record->parceiro_id,
        ];

        $record->update($data);

        app(AgendamentoHistoricoService::class)->registrarAlteracoes(
            agendamento: $record,
            tipoEvento: 'EDITADO',
            antes: $antes,
            depois: [
                'veiculo_id' => $record->veiculo_id,
                'data_agendamento' => optional($record->data_agendamento)->format('Y-m-d'),
                'data_limite' => optional($record->data_limite)->format('Y-m-d'),
                'servico_id' => $record->servico_id,
                'posicao' => $record->posicao,
                'plano_preventivo_id' => $record->plano_preventivo_id,
                'observacao' => $record->observacao,
                'parceiro_id' => $record->parceiro_id,
            ],
            descricao: 'Agendamento editado pela tela de edição.',
            userId: Auth::id(),
        );

        return $record;
    }
}
