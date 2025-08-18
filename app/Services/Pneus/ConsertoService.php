<?php

namespace App\Services\Pneus;

use App\Models\Conserto;
use App\Models\Pneu;

class ConsertoService
{
    public function create(Pneu $pneu, array $data): ?Conserto
    {
        $data['pneu_id'] = $pneu->id;
        return Conserto::create($data);
    }
}
