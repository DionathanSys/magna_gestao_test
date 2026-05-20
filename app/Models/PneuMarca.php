<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PneuMarca extends Model
{
    public function pneus(): HasMany
    {
        return $this->hasMany(Pneu::class, 'pneu_marca_id');
    }
}
