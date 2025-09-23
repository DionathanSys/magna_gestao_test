<?php

namespace App\Services\Veiculo\Queries;

use App\Models;

class GetQuilometragemLimiteMovimentacao
{

    protected int $kmMargem = 3000;

    /**
     * Retorna a quilometragem máxima permitida para movimentação do veículo.
     *
     * @param int $veiculoId
     * @return array ['km_maximo' => int, 'km_minimo' => int]
     */
    public function handle(int $veiculoId): array
    {
        //TODO: tornar kmMargem configurável via parâmetro ou configuração
        //TODO: cachear valores de quilometragem atual do veículo
        $veiculo = Models\Veiculo::query()
            ->find($veiculoId);

        return [
            'km_maximo' => ($veiculo?->quilometragem_atual ?? 0) + $this->kmMargem,
            'km_minimo' => max(0, ($veiculo?->quilometragem_atual ?? 0) - $this->kmMargem),
        ];
    }


}
