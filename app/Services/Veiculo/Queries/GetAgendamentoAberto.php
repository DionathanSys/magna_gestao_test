<?php

namespace App\Services\Veiculo\Queries;

use App\Services;
use Illuminate\Support\Facades\Log;

class GetAgendamentoAberto
{
    public function handle(int $veiculoId): bool
    {
        Log::debug('Consultando agendamento aberto para o veículo ID: '.$veiculoId);
        $service = new Services\Agendamento\AgendamentoService;
        $agendamentos = $service->getAgendamentoAbertoByVeiculo($veiculoId);

        return $agendamentos->isNotEmpty();
    }
}
