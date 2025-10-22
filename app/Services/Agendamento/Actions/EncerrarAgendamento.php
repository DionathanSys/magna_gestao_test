<?php

namespace App\Services\Agendamento\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use App\Traits\UserCheckTrait;

class EncerrarAgendamento
{
    use UserCheckTrait;

    public function __construct(protected Models\Agendamento $agendamento)
    {
    }

    public function handle(): bool
    {

        $this->validate();

        $data['status']         = StatusOrdemServicoEnum::CONCLUIDO;
        $data['updated_by']     = $this->getUserIdChecked();
        $data['data_realizado'] = now();

        $this->agendamento->update($data);

        return true;
    }

    public function validate(): void
    {
        if (in_array($this->agendamento->status, [StatusOrdemServicoEnum::CONCLUIDO, StatusOrdemServicoEnum::CANCELADO])) {
            throw new \InvalidArgumentException('Agendamento pode estar com status Concluído ou Cancelado.');
        }

    }
}
