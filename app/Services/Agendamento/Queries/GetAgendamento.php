<?php

namespace App\Services\Agendamento\Queries;

use App\Models;
use Illuminate\Database\Eloquent\Collection;

class GetAgendamento
{
    public function emAberto(array|int $veiculoId, ?string $periodo, ?string $atePeriodo): ?Collection
    {
        $query = Models\Agendamento::query()
            ->doVeiculo($veiculoId)
            ->abertos();

        if ($atePeriodo) {
            $query->entreDatas($periodo, $atePeriodo);
        } elseif ($periodo) {
            $query->agendadosPara($periodo);
        }

        return $query->get();
    }
}
