<?php

namespace App\Services\Agendamento\Queries;

use App\{Models, Services, Enum};
use Illuminate\Database\Eloquent\Collection;

class GetAgendamentoAberto
{
    public function handle(array $veiculoIds): ?Collection
    {
        return Models\Agendamento::query()
            ->whereIn('veiculo_id', $veiculoIds)
            ->whereIn('status', [
                Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE,
                Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO,
            ])
            ->get();
    }
}
