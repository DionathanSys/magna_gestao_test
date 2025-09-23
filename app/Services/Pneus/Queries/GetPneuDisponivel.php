<?php

namespace App\Services\Pneus\Queries;

use App\Models;
use App\Enum;

class GetPneuDisponivel
{
    public function handle(): array
    {
        return Models\Pneu::query()
            ->where('status', Enum\Pneu\StatusPneuEnum::DISPONIVEL)
            ->where('local', Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO)
            ->whereDoesntHave('veiculo')
            ->pluck('numero_fogo', 'id')
            ->toArray();
    }
}
