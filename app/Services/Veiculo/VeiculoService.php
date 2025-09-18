<?php

namespace App\Services\Veiculo;

use Illuminate\Support\Facades\Cache;

class VeiculoService
{
    public function getKmMedio(int $veiculoId): float
    {
        $veiculo = \App\Models\Veiculo::query()
            ->select('km_medio')
            ->findOrFail($veiculoId);
        return $veiculo->km_medio ?? 0.0;
    }

    public function getKmAtualVeiculos(array $veiculos): array
    {
        $veiculos = \App\Models\Veiculo::query()
            ->select('id', 'placa', 'km_medio')
            ->with('kmAtual')
            ->whereIn('id', $veiculos)
            ->get();

        $resultado = [];

        foreach ($veiculos as $veiculo) {
            $resultado[$veiculo->id] = [
                'placa' => $veiculo->placa,
                'km_atual' => $veiculo->kmAtual?->quilometragem ?? 0,
                'km_medio' => $veiculo->km_medio ?? 0,
            ];
        }

        return $resultado;
    }

    public static function getQuilometragemAtualByVeiculoId(int $veiculoId): int
    {
        return Cache::remember('km_atual_veiculo_id_' . $veiculoId, 86400, function () use ($veiculoId) {
            $veiculo = \App\Models\Veiculo::query()
                ->select('id', 'placa')
                ->findOrFail($veiculoId);

            return $veiculo->kmAtual?->quilometragem ?? 0;

        });

    }
}
