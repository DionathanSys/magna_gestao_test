<?php

namespace App\Services\PlanoManutencao\Queries;

use App\Models;

class GetPlanos
{
    public function handle(int $veiculoId)
    {
        return Models\PlanoPreventivo::query()
            ->join('planos_manutencao_veiculo', 'planos_manutencao_veiculo.plano_preventivo_id', '=', 'planos_preventivo.id')
            ->where('planos_manutencao_veiculo.veiculo_id', $veiculoId)
            ->where('planos_preventivo.is_active', true)
            ->orderBy('planos_preventivo.descricao')
            ->pluck('planos_preventivo.descricao', 'planos_preventivo.id');
    }

    // public function handleMultiple(array $veiculoIds)
    // {
    //     return \App\Models\PlanoPreventivo::query()
    //         ->join('planos_manutencao_veiculo', 'planos_manutencao_veiculo.plano_preventivo_id', '=', 'planos_preventivo.id')
    //         ->whereIn('planos_manutencao_veiculo.veiculo_id', $veiculoIds)
    //         ->where('planos_preventivo.is_active', true)
    //         ->orderBy('planos_preventivo.descricao')
    //         ->pluck('planos_preventivo.descricao', 'planos_preventivo.id');
    // }
}
