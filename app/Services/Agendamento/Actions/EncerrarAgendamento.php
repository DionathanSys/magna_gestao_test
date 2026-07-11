<?php

namespace App\Services\Agendamento\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use App\Services\Agendamento\AgendamentoHistoricoService;
use App\Traits\UserCheckTrait;

class EncerrarAgendamento
{
    use UserCheckTrait;

    public function __construct(protected Models\Agendamento $agendamento) {}

    public function handle(): bool
    {

        $this->validate();

        $data['status'] = StatusOrdemServicoEnum::CONCLUIDO;
        $data['updated_by'] = $this->getUserIdChecked();
        $data['data_realizado'] = now();

        $this->agendamento->update($data);

        app(AgendamentoHistoricoService::class)->registrar(
            agendamento: $this->agendamento,
            tipoEvento: 'ENCERRADO',
            descricao: 'Agendamento encerrado.',
            dados: [
                'status' => $this->agendamento->status?->value,
                'data_realizado' => $this->agendamento->data_realizado?->format('Y-m-d H:i:s'),
            ],
            userId: $this->getUserIdChecked(),
        );

        return true;
    }

    public function validate(): void
    {
        if (in_array($this->agendamento->status, [StatusOrdemServicoEnum::CONCLUIDO, StatusOrdemServicoEnum::CANCELADO])) {
            throw new \InvalidArgumentException('Agendamento pode estar com status Concluído ou Cancelado.');
        }

    }
}
