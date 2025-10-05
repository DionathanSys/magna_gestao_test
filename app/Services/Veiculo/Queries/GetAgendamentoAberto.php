<?php

namespace App\Services\Veiculo\Queries;

use App\{Models, Services, Enum};

class GetAgendamentoAberto
{
    public function handle(int $veiculoId): bool
    {
        $service = new Services\Agendamento\AgendamentoService();
        return $service->getAgendamentoAbertoByVeiculo($veiculoId) !== null;
    }
}
