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
            $query->where(function ($builder) use ($search) {
                $builder->where('numero_fogo', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });

            $query->orderByRaw(
                'case when id = ? then 0 when numero_fogo = ? then 1 when numero_fogo like ? then 2 else 3 end',
                [is_numeric($search) ? (int) $search : 0, $search, $search.'%']
            );
        } else {
            $query->orderBy('numero_fogo');
        }

        return $query
            ->limit($search ? 200 : 50)
            ->pluck('numero_fogo', 'id')
            ->toArray();
    }
}
