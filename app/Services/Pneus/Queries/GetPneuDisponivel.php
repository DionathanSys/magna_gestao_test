<?php

namespace App\Services\Pneus\Queries;

use App\Models;
use App\Enum;

class GetPneuDisponivel
{
    public function handle($search)
    {
        ds($search)->label('Search Term');
        return Models\Pneu::query()
            ->where('status', Enum\Pneu\StatusPneuEnum::DISPONIVEL)
            ->where('local', Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO)
            ->where('numero_fogo', 'like', '%'.$search.'%')
            ->whereDoesntHave('veiculo')
            ->pluck('numero_fogo', 'id');
    }
}
