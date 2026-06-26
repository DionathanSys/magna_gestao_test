<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PneuModelo extends Model
{
    public function marca(): BelongsTo
    {
        return $this->belongsTo(PneuMarca::class, 'pneu_marca_id');
    }

    public function pneus(): HasMany
    {
        return $this->hasMany(Pneu::class, 'pneu_modelo_id');
    }
}
