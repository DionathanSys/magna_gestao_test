<?php

namespace App\Services\Agendamento\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use App\Services\Agendamento\AgendamentoHistoricoService;
use App\Traits\UserCheckTrait;

class CancelarAgendamento
{
    use UserCheckTrait;

    public function __construct(protected Models\Agendamento $agendamento) {}

    public function handle(): bool
    {
        $this->validate();

        $data['status'] = StatusOrdemServicoEnum::CANCELADO;
        $data['updated_by'] = $this->getUserIdChecked();

        $this->agendamento->update($data);

        app(AgendamentoHistoricoService::class)->registrar(
            agendamento: $this->agendamento,
            tipoEvento: 'CANCELADO',
            descricao: 'Agendamento cancelado.',
            dados: [
                'status' => $this->agendamento->status?->value,
            ],
            userId: $this->getUserIdChecked(),
        );

        return true;
    }

    public function validate(): void
    {
        if ($this->agendamento->status != StatusOrdemServicoEnum::PENDENTE) {
            throw new \InvalidArgumentException('Agendamento deve estar pendente.');
        }

        if ($this->agendamento->ordem_servico_id) {
            throw new \InvalidArgumentException('Agendamento já está vinculado a uma ordem de serviço.');
        }
    }
}
