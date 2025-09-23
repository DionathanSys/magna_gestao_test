<?php

namespace App\Services\Veiculo\Queries;

use App\Models;

class GetQuilometragemUltimoMovimento
{
    /**
     * Handle the query to get the last movement mileage for a given vehicle ID.
     *
     * @param int $veiculoId
     * @return int
     */
    public function handle(int $veiculoId): int
    {
        $veiculo = Models\Veiculo::query()
            ->with('pneus')
            ->find($veiculoId);

        $ultimoMovimento = $veiculo?->pneus->sortByDesc('km_inicial')->first();

        return $ultimoMovimento->km_inicial ?? 0;
    }

    /**
     * Handle multiple vehicle IDs and return their last movement mileage.
     *
     * @param array $veiculoIds
     * @return array
     */
    public function handleMultiple(array $veiculoIds): array
    {
        $veiculos = Models\Veiculo::query()
            ->with('pneus')
            ->whereIn('id', $veiculoIds)
            ->get();

        $resultado = [];

        foreach ($veiculos as $veiculo) {
            $ultimoMovimento = $veiculo->pneus->sortByDesc('km_inicial')->first();
            $resultado[$veiculo->id] = $ultimoMovimento->km_inicial ?? 0;
        }

        return $resultado;
    }

}
