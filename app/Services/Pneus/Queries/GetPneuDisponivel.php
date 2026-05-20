<?php

namespace App\Services\Pneus\Queries;

use App\Enum;
use App\Models;

class GetPneuDisponivel
{
    public function handle(?string $search = null): array
    {
        $query = Models\Pneu::query()
            ->where('status', Enum\Pneu\StatusPneuEnum::DISPONIVEL)
            ->where('local', Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO)
            ->whereDoesntHave('veiculo');

        if ($search) {
            $query->where('numero_fogo', 'like', "%{$search}%");
        }

        return $query
            ->orderBy('numero_fogo')
            ->limit(50)
            ->pluck('numero_fogo', 'id')
            ->toArray();
    }
}
