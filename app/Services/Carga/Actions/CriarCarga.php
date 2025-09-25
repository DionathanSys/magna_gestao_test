<?php

namespace App\Services\Carga\Actions;

use App\Models;

class CriarCarga
{
    public function handle(array $data): ?Models\CargaViagem
    {
        return Models\CargaViagem::create($data);
    }
}
