<?php

namespace App\Services\Agendamento\Queries;

use App\{Models, Services, Enum};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class GetAgendamentoAberto
{
    public function handle(array $veiculoIds): ?Collection
    {
        Log::debug('Consultando agendamentos abertos para os veículos IDs: ' . implode(', ', $veiculoIds));
        $agendamentos =  Models\Agendamento::query()
            ->whereIn('veiculo_id', $veiculoIds)
            ->whereIn('status', [
                Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE,
                Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO,
            ])
            ->get();
        Log::debug('Agendamentos encontrados: ' . $agendamentos->count(), ['agendamentos' => $agendamentos->toArray()]);
        return $agendamentos;
    }
}
