<?php

namespace App\Services\Veiculo\Queries;

use App\Models;

class GetVeiculo
{
    public function handle(int $veiculoId): ?Models\Veiculo
    {
        return Models\Veiculo::query()
            ->findOrFail($veiculoId);
    }
}
