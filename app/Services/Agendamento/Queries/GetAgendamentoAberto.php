<?php

namespace App\Services\Agendamento\Queries;

use App\Models;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class GetAgendamentoAberto
{
    public function handle(array $veiculoIds): ?Collection
    {
        $agendamentos = Models\Agendamento::query()
            ->doVeiculo($veiculoIds)
            ->abertos()
            ->get();
        Log::debug('Agendamentos encontrados: '.$agendamentos->count(), ['agendamentos' => $agendamentos->toArray()]);

        return $agendamentos;
    }
}
