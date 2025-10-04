<?php

namespace App\Services\Checklist\Actions;

use App\{Services, Models, Enum};

class AgendarPendenciasChecklist
{
    public function handle(int $checklistId, int $veiculoId, array $data)
    {
        $this->validate($data);

        $service = new Services\Agendamento\AgendamentoService();

        foreach ($data as $key => $pendencia) {
            $service->create([
                'veiculo_id' => $veiculoId,
                'servico_id' => 184,
                'observacao' => "Pendência do checklist ID: {$checklistId}, Item: {$pendencia['item']}",
            ]);
        }

        return;
    }

    private function validate(array $data): void
    {
        if(empty($data)) {
            throw new \InvalidArgumentException('Nenhuma pendência para agendar.');
        }

        $errors = [];
        foreach ($data as $pendencia) {
            if($pendencia['status'] || $pendencia['corrigido']) {
                $errors[] = "Item {$pendencia['item']} não pode ser agendado. Status: " . ($pendencia['status'] ? 'Sim' : 'Não') . ", Corrigido: " . ($pendencia['corrigido'] ? 'Sim' : 'Não');
            }
        }

        if(!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }
}
