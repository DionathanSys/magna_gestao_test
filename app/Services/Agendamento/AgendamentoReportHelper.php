<?php

namespace App\Services\Agendamento;

use Illuminate\Support\Collection;

class AgendamentoReportHelper
{
    public static function emAberto(int|array|null $veiculoId, string|array|null $periodo): ?Collection
    {
        $query = \App\Models\Agendamento::query();

        if (!is_null($veiculoId)) {
            if (is_array($veiculoId)) {
                $query->whereIn('veiculo_id', $veiculoId);
            } else {
                $query->where('veiculo_id', $veiculoId);
            }
        }

        if (!is_null($periodo)) {
            if (is_array($periodo) && count($periodo) === 2) {
                $query->whereBetween('data_agendamento', [
                    $periodo[0],
                    $periodo[1]
                ]);
            } elseif (is_string($periodo)) {

                $periodo = \Carbon\Carbon::createFromFormat('d/m/Y', $periodo)->format('Y-m-d');

                $query->whereDate('data_agendamento', $periodo);
            }
        }

        $result = $query->get()->toArray();

        return collect(['data' => $result]); 
    }
}