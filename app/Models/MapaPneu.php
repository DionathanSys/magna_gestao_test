<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapaPneu extends Model
{
    public function posicoes(): HasMany
    {
        return $this->hasMany(MapaPneuPosicao::class);
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }
}
