<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoVeiculo extends Model
{
    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }
}
