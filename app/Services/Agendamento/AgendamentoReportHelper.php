<?php

namespace App\Services\Agendamento;

use App\Models\Agendamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AgendamentoReportHelper
{
    public static function emAberto(int|array|null $veiculoId, string|array|null $periodo): ?Collection
    {
        $query = Agendamento::query()
            ->doVeiculo($veiculoId)
            ->abertos();

        if (! is_null($periodo)) {
            if (is_array($periodo) && count($periodo) === 2) {
                $query->entreDatas($periodo[0], $periodo[1]);
            } elseif (is_string($periodo)) {
                $periodo = Carbon::createFromFormat('d/m/Y', $periodo)->format('Y-m-d');

                $query->agendadosPara($periodo);
            }
        }

        $result = $query->get()->toArray();

        return collect(['data' => $result]);
    }
}
