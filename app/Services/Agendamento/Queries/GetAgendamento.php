<?php

namespace App\Services\Agendamento\Queries;

use App\{Models, Services, Enum};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class GetAgendamento
{
    public function pendentes(string|null $periodo, string|null $atePeriodo): ?Collection
    {
        $query =  Models\Agendamento::query()
            ->whereIn('status', [
                Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE,
                Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO,
            ]);

        if ($atePeriodo) {
            $query->whereBetween('data_agendamento', [$periodo, $atePeriodo]);
        } else if ($periodo) {
            $query->where('data_agendamento', '=', $periodo);
        }
    
        return $query->get();

    }
}
