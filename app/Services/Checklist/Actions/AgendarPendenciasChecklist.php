<?php

namespace App\Services\Checklist\Actions;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Services;
use Illuminate\Support\Facades\Log;

class AgendarPendenciasChecklist
{
    public function handle(int $checklistId, int $veiculoId, array $data)
    {
        $this->validate($data);

        $checklistServiceId = (int) config('agendamento.checklist_service_id');

        if ($checklistServiceId <= 0) {
            throw new \InvalidArgumentException('Serviço de checklist não configurado para agendamentos.');
        }

        $service = new Services\Agendamento\AgendamentoService;

        foreach ($data as $key => $pendencia) {
            Log::debug(__METHOD__.' - '.__LINE__, [
                'pendencia' => $pendencia,
            ]);
            $service->create([
                'veiculo_id' => $veiculoId,
                'servico_id' => $checklistServiceId,
                'categoria' => CategoriaAgendamentoEnum::CHECKLIST,
                'observacao' => "Pendência do checklist ID: {$checklistId}, Item: {$pendencia['item']}, Obs.: {$pendencia['observacoes']}",
            ]);
        }

    }

    private function validate(array $data): void
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Nenhuma pendência para agendar.');
        }

        $errors = [];
        foreach ($data as $pendencia) {
            if ($pendencia['status'] || $pendencia['corrigido']) {
                $errors[] = "Item {$pendencia['item']} não pode ser agendado. Status: ".($pendencia['status'] ? 'Sim' : 'Não').', Corrigido: '.($pendencia['corrigido'] ? 'Sim' : 'Não');
            }
        }

        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }
}
